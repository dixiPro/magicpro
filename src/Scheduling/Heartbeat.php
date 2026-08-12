<?php

namespace MagicProSrc\Scheduling;

/**
 * Отметка планировщика: раз в минуту переписывает свой файл.
 *
 * Больше ни для чего она не нужна, но без неё нельзя ответить, жив ли крон.
 * Остальные задачи расписания следов не оставляют — пустая очередь писем ничего
 * не пишет, и молчание не отличить от смерти. Диагностика в админке смотрит
 * возраст этого файла.
 *
 * Читается только mtime, дата внутри — чтобы файл был понятен при осмотре руками.
 */
class Heartbeat
{
    /** Путь от storage. Тот же знает AdminController. */
    public const FILE = 'app/private/magic/heartbeat.txt';

    public function __invoke(): void
    {
        $file      = storage_path(self::FILE);
        $directory = dirname($file);

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents($file, now()->format('Y-m-d H:i:s'));
    }
}
