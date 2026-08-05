<?php

namespace MagicProSrc\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;


abstract class AbstractApi
{
    protected array $map = [];

    public static function run(string $command, array $params = []): array
    {
        $service = new static();
        $params['command'] = $command;

        try {
            if (!isset($service->map[$command])) {
                throw new \Exception('Unknown command: ' . $command);
            }

            $method = $service->map[$command];

            if (!method_exists($service, $method)) {
                throw new \Exception('Handler not implemented: ' . $method);
            }

            $data = $service->$method($params);

            return [
                'status'   => true,
                'errorMsg' => '',
                'data'     => $data,
                'request'  => $params,
            ];
        } catch (\Throwable $e) {
            return [
                'status'   => false,
                'line'     => $e->getFile() . ' ' . $e->getLine(),
                'errorMsg' => $e->getMessage(),
                'data'     => [],
                'request'  => $params,
            ];
        }
    }

    /**
     * Same as run(), but for internal calls that must succeed: an error is
     * rethrown instead of being reported through the status flag, and only
     * the data part is returned. run() itself never throws, so calling it
     * directly and ignoring the status silently continues on failure.
     */
    public static function runOrFail(string $command, array $params = []): array
    {
        $result = static::run($command, $params);

        if (!$result['status']) {
            throw new \Exception($result['errorMsg']);
        }

        return $result['data'];
    }

    public function handle(Request $request): JsonResponse
    {
        $params = $request->all();

        $command = (string) ($params['command'] ?? '');

        unset($params['command']);

        $result = static::run($command, $params);

        return response()->json($result);
    }
}
