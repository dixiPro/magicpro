<?php

namespace MagicProSrc\test;

class WriteCurrentTime
{
    public function __invoke(): void
    {
        $directory = storage_path('test');

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents(
            $directory . '/curTime.txt',
            now()->format('Y-m-d H:i:s')
        );
    }
}
