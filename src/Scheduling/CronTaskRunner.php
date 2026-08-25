<?php

namespace MagicProSrc\Scheduling;

use MagicProDatabaseModels\MagicProCronTask;
use MagicProSrc\Config\MagicGlobals;

/**
 * Calls the method of a cron task.
 *
 * A task points at one public method of a MagicPro controller and cron calls
 * it straight: no Request, no Env, no view. The method is an entry point of
 * its own — process() may call it too, when the same job has to be reachable
 * by URL, but that is the controller's business, not cron's.
 *
 * Because handle() is not involved, nothing swallows an exception from the
 * method any more: whatever breaks inside lands in the log with its real text,
 * and the "run now" button shows it on the screen.
 *
 * The try/catch here is not about judging the result. It protects the
 * neighbours: one broken task must not take down the whole scheduler pass.
 */
class CronTaskRunner
{
    /** Log file name, storage/logs/cron.log. */
    public const LOG = 'cron';

    /**
     * Parameters go as one array, the json of the task, and the method takes
     * one array — no other reading of the same thing.
     *
     * @return array{ms: int, error: string} ms is how long the call took
     */
    public static function run(MagicProCronTask $task): array
    {
        $start  = microtime(true);
        $params = $task->params ?? [];

        ['article' => $article, 'method' => $method] = CronTaskChecker::parse($task->controller);

        try {
            // The only thing asked before the call. CronTaskChecker is not
            // called here: it goes to the database, and the admin panel has
            // already shown its answer. A method that is not there throws by
            // itself, with a text no worse than ours.
            if ($article === '' || $method === '' || ! class_exists(CronTaskChecker::className($article))) {
                throw new \Exception(CronTaskChecker::ERRORS['class_missing']);
            }

            // Stamped before the call: the mark says the scheduler reached this
            // task, not that the method did its job.
            $task->forceFill(['last_run_at' => now()])->save();

            // app() and not new: a controller may ask for its dependencies in
            // the constructor.
            $controller = app(CronTaskChecker::className($article));

            // Whatever comes back is dropped. Cron has nothing to do with it.
            $controller->{$method}($params);

            $ms = self::ms($start);

            if (MagicGlobals::$INI['CRON_LOG_SUCCESS'] ?? true) {
                self::log($task, $article, $method, $ms, ['params' => $params]);
            }

            return ['ms' => $ms, 'error' => ''];
        } catch (\Throwable $e) {
            $ms = self::ms($start);

            // Troubles are written whatever the setting says.
            self::log($task, $article, $method, $ms, ['error' => $e->getMessage()]);

            return ['ms' => $ms, 'error' => $e->getMessage()];
        }
    }

    private static function log(
        MagicProCronTask $task,
        string $article,
        string $method,
        int $ms,
        array $extra
    ): void {
        \MproHelper::addLog(self::LOG, array_merge([
            'id'         => $task->id,
            'name'       => $task->name,
            'controller' => $article,
            'method'     => $method,
        ], $extra, ['ms' => $ms]));
    }

    private static function ms(float $start): int
    {
        return (int) round((microtime(true) - $start) * 1000);
    }
}
