<?php

namespace MagicProSrc\Scheduling;

use Illuminate\Support\Facades\Cache;
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
    /**
     * Name of the log. The file on disk is dated — `storage/logs/cron-ГГГГ-ММ-ДД.log`:
     * `addLog()` hands the name to a rotating handler, and a day is a file.
     */
    public const LOG = 'cron';

    /**
     * How long the lock of a running task lives, in seconds.
     *
     * One lock for both ways in — the scheduler and the «run now» button — so
     * a task never runs twice at once: the button pressed while the scheduler
     * is inside the task gets «already running», and the other way round.
     *
     * Released when the task ends. The term is for a process that was killed
     * midway and could not release it: `withoutOverlapping()` held such a lock
     * for a day, and the task was silently skipped all that time. The price of
     * ten minutes: a task that runs longer may be started once more over
     * itself.
     */
    public const LOCK_SECONDS = 600;

    public const ERRORS = [
        'running' => 'the task is already running',
    ];

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

        // Not written into the log: the scheduler meets a busy task every
        // minute while a long one runs, and that is not news.
        $lock = self::lock($task);

        if ($lock === false) {
            return ['ms' => 0, 'error' => self::ERRORS['running']];
        }

        try {
            // The only thing asked before the call. CronTaskChecker is not
            // called here: it goes to the database, and the admin panel has
            // already shown its answer. A method that is not there throws by
            // itself, with a text no worse than ours.
            if ($article === '' || $method === '' || ! class_exists(CronTaskChecker::className($article))) {
                throw new \Exception(CronTaskChecker::ERRORS['class_missing']);
            }

            // the same name rule as the admin panel: without it a task written
            // past the panel could call a constructor the controller declares
            if (! CronTaskChecker::methodName($method)) {
                throw new \Exception(CronTaskChecker::ERRORS['method_invalid']);
            }

            // Stamped before the call: the mark says the scheduler reached this
            // task, not that the method did its job.
            $task->forceFill(['last_run_at' => now()])->save();

            // app() and not new: a controller may ask for its dependencies in
            // the constructor.
            $controller = app(CronTaskChecker::className($article));

            // The checker refuses an inherited method when a task is saved, but
            // tasks saved before it did are still in the table. `handle()` is
            // the dangerous one: it catches everything inside and answers with
            // a 500 response, so cron would see no exception and write down a
            // successful run.
            if (! CronTaskChecker::ownMethod(CronTaskChecker::className($article), $method)) {
                throw new \Exception(CronTaskChecker::ERRORS['method_foreign']);
            }

            // Whatever comes back is dropped. Cron has nothing to do with it.
            $controller->{$method}($params);

            $ms = self::ms($start);

            if (MagicGlobals::$INI['CRON_LOG_SUCCESS'] ?? true) {
                // имена, а не значения: в параметрах задачи живут токены
                // ботов и ключи, а лог хранится две недели и читается многими
                self::log($task, $article, $method, $ms, ['params' => implode(', ', array_keys($params))]);
            }

            return ['ms' => $ms, 'error' => ''];
        } catch (\Throwable $e) {
            $ms = self::ms($start);

            // Troubles are written whatever the setting says.
            self::log($task, $article, $method, $ms, ['error' => $e->getMessage()]);

            return ['ms' => $ms, 'error' => $e->getMessage()];
        } finally {
            try {
                $lock?->release();
            } catch (\Throwable) {
                // not released — it goes away by its term
            }
        }
    }

    /**
     * The lock of the task: the lock itself, false when somebody holds it, null
     * when the cache cannot give one.
     *
     * A cache that does not answer — a file store owned by another user — does
     * not stop the task: running it without a lock is the old behaviour, and a
     * task that is not run at all is worse.
     */
    private static function lock(MagicProCronTask $task): \Illuminate\Contracts\Cache\Lock|false|null
    {
        try {
            $lock = Cache::lock('magicpro:cron:run:' . $task->id, self::LOCK_SECONDS);

            return $lock->get() ? $lock : false;
        } catch (\Throwable) {
            return null;
        }
    }

    private static function log(
        MagicProCronTask $task,
        string $article,
        string $method,
        int $ms,
        array $extra
    ): void {
        // addLog() never throws: a log that cannot be written does not turn a
        // successful task into a failed one
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
