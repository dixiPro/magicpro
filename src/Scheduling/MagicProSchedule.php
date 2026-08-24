<?php

namespace MagicProSrc\Scheduling;

use Cron\CronExpression;
use Illuminate\Console\Scheduling\Schedule;
use MagicProDatabaseModels\MagicProCronTask;
use MagicProSrc\Mail\API_Mail;

class MagicProSchedule
{
    public function register(Schedule $schedule): void
    {
        // Отметка живого крона, её читает диагностика в админке.
        $schedule
            ->call(new Heartbeat())
            ->everyMinute()
            ->name('magicpro:heartbeat');

        // Очередь писем. Свой замок на 300 секунд стоит внутри sendQueue,
        // поэтому долгая рассылка не наложится на следующую минуту.
        $schedule
            ->call(fn () => API_Mail::run('sendQueue', []))
            ->everyMinute()
            ->name('magicpro:sendQueue');

        // Задачи из админки. Всё, что ниже, обязано молчать при любой беде:
        // heartbeat и очередь писем уже зарегистрированы и не должны страдать
        // из-за кривой строки в базе.
        $this->registerCronTasks($schedule);
    }

    /**
     * Задачи, заведённые в админке.
     *
     * Список читается заново при каждом запуске планировщика, то есть раз в
     * минуту: правка в админке должна действовать сразу, без кеша и без
     * перезапуска приложения.
     */
    private function registerCronTasks(Schedule $schedule): void
    {
        try {
            $tasks = MagicProCronTask::where('enabled', true)->get();
        } catch (\Throwable $e) {
            // Базы нет, таблицы нет, база отвалилась — расписание просто
            // остаётся без динамических задач. На свежей установке, до
            // миграций, artisan должен работать.
            return;
        }

        foreach ($tasks as $task) {
            try {
                // Ларавель принимает строку не глядя, а спотыкается о неё
                // позже, уже при проверке «пора ли», и этот бросок нам не
                // поймать: он вне регистрации.
                if (! CronExpression::isValidExpression($task->cron)) {
                    throw new \Exception('invalid cron expression: ' . $task->cron);
                }

                // name() до withoutOverlapping(): замок берёт имя отсюда, без
                // него ларавель бросает LogicException.
                $schedule
                    ->call(fn () => CronTaskRunner::run($task))
                    ->cron($task->cron)
                    ->name('magicpro:dynamic:' . $task->id)
                    ->withoutOverlapping();
            } catch (\Throwable $e) {
                \MproHelper::addLog(CronTaskRunner::LOG, [
                    'stage' => 'register',
                    'id'    => $task->id,
                    'name'  => $task->name,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
