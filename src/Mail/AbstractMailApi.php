<?php

namespace MagicProSrc\Mail;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Own copy of MagicProSrc\Api\AbstractApi for the mail subsystem.
 *
 * Intentionally a separate class (not extended from the shared AbstractApi):
 * the mail API may need to diverge later, and keeping it apart means changes
 * here never touch the rest of the code. For now it is a plain copy.
 *
 * Dispatches a command to a method via $map, passes params as a plain array,
 * lets handlers throw exceptions, and always returns the standard shape:
 * status / errorMsg / data / request.
 */
abstract class AbstractMailApi
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
                'request'  => $params,
            ];
        }
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
