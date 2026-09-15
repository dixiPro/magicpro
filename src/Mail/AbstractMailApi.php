<?php

namespace MagicProSrc\Mail;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * The dispatcher of the mail api, a relative of MagicProSrc\Api\AbstractApi.
 *
 * Intentionally a separate class and not a child of the shared one: the mail
 * api may need to diverge, and keeping it apart means changes here never touch
 * the rest of the code. It started as a copy and is one no longer, so what
 * differs is written down here:
 *
 * - the shared one has `runOrFail()` for internal calls that must succeed;
 *   here there is no caller that needs it;
 * - the shared one puts the file and the line of an exception into the answer,
 *   here they go into the `mail` log: this answer reaches a browser.
 *
 * Dispatches a command to a method via $map, passes params as a plain array,
 * lets handlers throw exceptions, and always returns the standard shape:
 * status / errorMsg / data / request — the same one whether it worked or not.
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
            // где именно упало — в лог, а не в ответ: ответ уезжает в браузер и
            // раскрывал бы устройство файловой системы сервера
            \MproHelper::addLog('mail', [
                'command' => $command,
                'error'   => $e->getMessage(),
                'where'   => $e->getFile() . ' ' . $e->getLine(),
            ]);

            // форма ответа одна и та же при удаче и при ошибке: тот, кто читает
            // `data`, не должен проверять, есть ли она вообще
            return [
                'status'   => false,
                'errorMsg' => $e->getMessage(),
                'data'     => [],
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
