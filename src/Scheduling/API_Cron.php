<?php

namespace MagicProSrc\Scheduling;

use Cron\CronExpression;
use MagicProDatabaseModels\MagicProCronTask;
use MagicProSrc\Api\AbstractApi;

/**
 * Cron tasks of the admin panel: POST /a_dmin/api/cron.
 *
 * Every command works "up to the first error" and throws; the answer shape is
 * built by AbstractApi::run().
 *
 * The article behind a task is checked by CronTaskChecker in three places —
 * save, list and runNow. list checks on every call on purpose: an article can
 * be renamed or deleted long after the task was created, and the screen is the
 * only place where that becomes visible.
 */
class API_Cron extends AbstractApi
{
    protected const ERRORS = [
        'id_required'         => 'id required',
        'task_not_found'      => 'task not found',
        'name_required'       => 'name required',
        'controller_required' => 'controller required',
        'params_not_json'     => 'params must be a json object',
        'cron_required'       => 'cron required',
        'cron_invalid'        => 'invalid cron expression',
    ];

    protected array $map = [
        'list'   => 'listTasks',
        'get'    => 'getTask',
        'save'   => 'saveTask',
        'toggle' => 'toggleTask',
        'delete' => 'deleteTask',
        'runNow' => 'runNow',
    ];

    /** The list, each task carrying the checker's answer about its article. */
    protected function listTasks(array $params): array
    {
        $tasks = MagicProCronTask::orderBy('id')->get();

        $list = [];

        foreach ($tasks as $task) {
            $row = $task->toArray();

            $row['check'] = CronTaskChecker::check($task->controller);

            $list[] = $row;
        }

        return ['list' => $list];
    }

    protected function getTask(array $params): array
    {
        $task = self::findTask($params);

        return [
            'task'  => $task->toArray(),
            'check' => CronTaskChecker::check($task->controller),
        ];
    }

    /**
     * Create or update: an `id` means update.
     *
     * A task that could never run is not stored. Both a broken cron string and
     * an article cron cannot reach are refused here, not discovered at night in
     * the log.
     */
    protected function saveTask(array $params): array
    {
        $name       = trim((string) ($params['name'] ?? ''));
        $controller = trim((string) ($params['controller'] ?? ''));
        $cron       = trim((string) ($params['cron'] ?? ''));

        if ($name === '') {
            throw new \Exception(self::ERRORS['name_required']);
        }

        if ($controller === '') {
            throw new \Exception(self::ERRORS['controller_required']);
        }

        $check = CronTaskChecker::check($controller);

        if (! $check['ok']) {
            throw new \Exception($check['error']);
        }

        if ($cron === '') {
            throw new \Exception(self::ERRORS['cron_required']);
        }

        if (! CronExpression::isValidExpression($cron)) {
            throw new \Exception(self::ERRORS['cron_invalid']);
        }

        $task = ! empty($params['id'])
            ? self::findTask($params)
            : new MagicProCronTask();

        $task->fill([
            'name'       => $name,
            'controller' => $controller,
            'params'     => self::readParams($params['params'] ?? []),
            'cron'       => $cron,
            'enabled'    => (bool) ($params['enabled'] ?? true),
        ]);

        $task->save();

        return ['task' => $task->toArray()];
    }

    protected function toggleTask(array $params): array
    {
        $task = self::findTask($params);

        $task->enabled = ! $task->enabled;
        $task->save();

        return [
            'id'      => $task->id,
            'enabled' => $task->enabled,
        ];
    }

    protected function deleteTask(array $params): array
    {
        $task = self::findTask($params);
        $id   = $task->id;

        $task->delete();

        return ['id' => $id];
    }

    /**
     * Run right now, past the scheduler.
     *
     * This is where a task is really checked. Cron calls the method straight,
     * nothing swallows an exception any more, so whatever the method says
     * about itself comes back here and goes on the screen — the button exists
     * to see exactly that.
     *
     * There is no lock here on purpose: withoutOverlapping sits on the
     * scheduler event and does not cover a manual run. Press the button while
     * the task is already running on schedule and it goes as a second pass —
     * that is the caller's business.
     */
    protected function runNow(array $params): array
    {
        $task  = self::findTask($params);
        $check = CronTaskChecker::check($task->controller);

        if (! $check['ok']) {
            throw new \Exception($check['error']);
        }

        // Задача идёт прямо в этом запросе. Оборвёт его nginx по таймауту или
        // закроют вкладку — php доработает задачу до конца: половина рассылки
        // хуже, чем ответ, который никто не увидел. Итог будет в логе cron.
        ignore_user_abort(true);
        set_time_limit(0);

        $res = CronTaskRunner::run($task);

        if ($res['error'] !== '') {
            throw new \Exception($res['error']);
        }

        return ['ms' => $res['ms']];
    }

    private static function findTask(array $params): MagicProCronTask
    {
        $id = (int) ($params['id'] ?? 0);

        if ($id <= 0) {
            throw new \Exception(self::ERRORS['id_required']);
        }

        $task = MagicProCronTask::find($id);

        if (! $task) {
            throw new \Exception(self::ERRORS['task_not_found']);
        }

        return $task;
    }

    /**
     * Parameters arrive as a string from the form or as an array from code.
     * Empty means an empty array; anything else must be a key-value object: the
     * method of the task takes it as one array, and a plain list has no names.
     *
     * Broken json is refused and not quietly taken for emptiness. A missing
     * quotation mark used to end like this: `json_decode()` answered `null`,
     * `null` counted as «no parameters», the operator saw a successful save,
     * and the task then ran without what it needed.
     */
    private static function readParams(mixed $params): array
    {
        if (is_string($params)) {
            $params = trim($params);

            if ($params === '') {
                return [];
            }

            $decoded = json_decode($params, true);

            // broken json, a literal `null`, a number, a string — none of them
            // is a set of parameters, and none of them is emptiness either: the
            // form has an empty field for that
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                throw new \Exception(self::ERRORS['params_not_json']);
            }

            $params = $decoded;
        }

        // an array from code may be empty, and that is emptiness
        if ($params === [] || $params === null) {
            return [];
        }

        if (! is_array($params) || array_is_list($params)) {
            throw new \Exception(self::ERRORS['params_not_json']);
        }

        return $params;
    }
}
