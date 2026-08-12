<?php

namespace MagicProSrc\Scheduling;

use Illuminate\Console\Scheduling\Schedule;
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
    }
}
