<?php

namespace MagicProSrc\Scheduling;

use Illuminate\Support\Facades\Cache;
use Cron\CronExpression;
use Illuminate\Console\Scheduling\Schedule;
use MagicProDatabaseModels\MagicProCronTask;
use MagicProSrc\Ai\AiSession;
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

        // Зависшие сеансы AI-агента. Браузер закрывает сеанс сам, но браузер
        // закрывают, перезагружают и просто бросают, а агент остаётся жить с
        // открытым MCP. Cleanup молчит, если настроек нет.
        $schedule
            ->call(fn () => AiSession::cleanup())
            ->everyMinute()
            ->name('magicpro:aiCleanup');

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

                // Без withoutOverlapping(): замок стоит внутри раннера, один
                // на расписание и на кнопку «Запустить сейчас», и живёт десять
                // минут, а не сутки, как у ларавеля после убитого процесса.
                $schedule
                    ->call(fn () => CronTaskRunner::run($task))
                    ->cron($task->cron)
                    ->name('magicpro:dynamic:' . $task->id);
            } catch (\Throwable $e) {
                // Регистрация идёт каждую минуту, и жалоба на одну и ту же
                // задачу писалась бы сорок тысяч раз в месяц, топя в себе всё
                // остальное. Пишем раз в час на задачу: сломанное расписание
                // никуда не денется, а лог остаётся читаемым.
                //
                // The complaint itself must not break anything. addLog() never
                // throws, but the cache may — a file store owned by another
                // user — and its exception would leave the register and take
                // the whole schedule with it, mail and heartbeat too.
                try {
                    $said = 'magic:cron:register:' . $task->id . ':' . md5((string) $e->getMessage());

                    if (Cache::add($said, 1, 3600)) {
                        \MproHelper::addLog(CronTaskRunner::LOG, [
                            'stage' => 'register',
                            'id'    => $task->id,
                            'name'  => $task->name,
                            'error' => $e->getMessage(),
                        ]);
                    }
                } catch (\Throwable) {
                }
            }
        }
    }
}
