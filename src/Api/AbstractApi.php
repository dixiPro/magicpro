<?php

namespace MagicProSrc\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;


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
        } catch (HttpExceptionInterface $e) {
            // abort() is a verdict on the whole request, not an answer of the
            // command: a site without its reCAPTCHA key must not tell visitors
            // they are robots. It goes on to Laravel and becomes the HTTP status
            \MproHelper::addLog('api', [
                'api'     => static::class,
                'command' => $command,
                'error'   => $e->getMessage(),
                'where'   => $e->getFile() . ' ' . $e->getLine(),
            ]);

            throw $e;
        } catch (\Throwable $e) {
            // где именно упало — в лог, а не в ответ: ответ уезжает в браузер и
            // раскрывал бы устройство файловой системы сервера
            \MproHelper::addLog('api', [
                'api'     => static::class,
                'command' => $command,
                'error'   => $e->getMessage(),
                'where'   => $e->getFile() . ' ' . $e->getLine(),
            ]);

            return [
                'status'   => false,
                'errorMsg' => $e->getMessage(),
                'data'     => $e instanceof ApiError ? ['errorCode' => $e->errorCode] : [],
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
            if (isset($result['data']['errorCode'])) {
                throw new ApiError($result['data']['errorCode'], $result['errorMsg']);
            }

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
