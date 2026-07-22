<?php

namespace MagicProSrc\Scheduling;

use Illuminate\Console\Scheduling\Schedule;
use MagicProSrc\test\WriteCurrentTime;

class MagicProSchedule
{
    public function register(Schedule $schedule): void
    {
        $schedule
            ->call(new WriteCurrentTime())
            ->everyMinute();
    }
}
