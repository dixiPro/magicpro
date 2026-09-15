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
            // где именно упало — в лог, а не в ответ: ответ уезжает в браузер,
            // а через МСП — агенту, и путь с номером строки там не нужен
            \MproHelper::addLog('api', [
                'api'     => static::class,
                'command' => $request->string('command')->toString(),
                'error'   => $e->getMessage(),
                'where'   => $e->getFile() . ' ' . $e->getLine(),
            ]);

            return response()->json([
                'status'   => false,
                'errorMsg' => $e->getMessage(),
                'data'     => [],
                'request' => $request->all(),
            ]);
        }
    }
}
