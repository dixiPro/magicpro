<?php

namespace MagicProSrc\Lenta;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Dispatcher of the feeds api. Commands live in its child, API_Feeds.
 *
 * A copy of MagicProSrc\Api\AbstractApi rather than a child of it, on purpose.
 * The two are built the same way and will probably stay identical for years,
 * but the day the feeds dispatcher needs a change it must not touch
 * authorization and mail. The mail subsystem did the same with its
 * AbstractMailApi. The price is a dozen duplicated lines, and it is cheaper
 * than the coupling.
 *
 * A command is looked up in $map and called with all the params in one flat
 * array. Handlers are free to throw: run() turns any error into a normal
 * answer, always of the same shape — status / errorMsg / data / request.
 */
abstract class AbstractFeedApi
{
    /** command => method of this class */
    protected array $map = [];

    /**
     * Runs a command. Never throws: an error becomes an answer with
     * status = false and the message in errorMsg.
     *
     * Validation of a record is the one error that keeps its detail — it comes
     * back in `errors`, laid out per field, so the form can highlight the input
     * that went wrong.
     */
    public static function run(string $command, array $params = []): array
    {
        $params['command'] = $command;

        try {
            return [
                'status'   => true,
                'errorMsg' => '',
                'data'     => (new static())->dispatch($command, $params),
                'request'  => $params,
            ];
        } catch (FeedValidationException $e) {
            return [
                'status'   => false,
                // разбор по полям идёт и в текст: клиент показывает тостом
                // errorMsg, и без этого видно «проверьте поля», но не какие
                'errorMsg' => $e->getMessage() . ': ' . self::errorsText($e->errors()),
                'errors'   => $e->errors(),
                'data'     => [],
                'request'  => $params,
            ];
        } catch (Throwable $e) {
            return [
                'status'   => false,
                'line'     => $e->getFile() . ' ' . $e->getLine(),
                'errorMsg' => $e->getMessage(),
                'data'     => [],
                'request'  => $params,
            ];
        }
    }

    /** Errors per field as one line: «title — обязательно; price — число». */
    protected static function errorsText(array $errors): string
    {
        $parts = [];

        foreach ($errors as $code => $messages) {
            $parts[] = $code . ' — ' . implode(', ', (array) $messages);
        }

        return implode('; ', $parts);
    }

    /**
     * Same command, for internal calls that have to succeed: the error is
     * thrown on instead of being reported through the status flag, and only the
     * data part comes back.
     *
     * run() never throws, so calling it inside the module and forgetting to
     * check the status would quietly continue after a failure.
     *
     * The command is dispatched directly rather than through run(): that keeps
     * the original exception, and with it the per-field errors of a validation
     * failure, which a rethrown copy would lose.
     */
    public static function runOrFail(string $command, array $params = []): mixed
    {
        $params['command'] = $command;

        return (new static())->dispatch($command, $params);
    }

    /** Entry point for http: the command comes in the body among the params. */
    public function handle(Request $request): JsonResponse
    {
        $params = $request->all();

        $command = (string) ($params['command'] ?? '');

        unset($params['command']);

        return response()->json(static::run($command, $params));
    }

    /**
     * Command to method. An unknown command and a method that is listed but not
     * written are both errors: silently doing nothing would look like success.
     */
    protected function dispatch(string $command, array $params): mixed
    {
        if (! isset($this->map[$command])) {
            throw new Exception('Unknown command: ' . $command);
        }

        $method = $this->map[$command];

        if (! method_exists($this, $method)) {
            throw new Exception('Handler not implemented: ' . $method);
        }

        return $this->$method($params);
    }
}
