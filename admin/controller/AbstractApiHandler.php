<?php

namespace MagicProAdminControllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

abstract class AbstractApiHandler
{
    protected array $map = [];

    public static function run(array $params): array
    {
        $service = new static();
        return $service
            ->handle(new Request($params))
            ->getData(true);
    }

    public function handle(Request $request): JsonResponse
    {
        try {
            $command = $request->string('command')->toString();

            if (!isset($this->map[$command])) {
                throw new \Exception('Unknown command: ' . $command);
            }

            $method = $this->map[$command];

            if (!method_exists($this, $method)) {
                throw new \Exception('Handler not implemented: ' . $method);
            }

            $data = $this->$method($request);

            return response()->json([
                'status'   => true,
                'errorMsg' => '',
                'data'     => $data,
                'request' => $request->all(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'   => false,
                'errorMsg' => $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine(),
                'data'     => [],
                'request' => $request->all(),
            ]);
        }
    }
}
