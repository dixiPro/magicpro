<?php

namespace MagicProAdminControllers;

use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\File;

/**
 * Форматирует Blade или PHP файл через Laravel Pint.
 * Работает с абсолютными путями, создаёт временную копию,
 * возвращает отформатированный текст и удаляет файл.
 */
class PintFormatter
{
    /**
     * Форматирует указанный файл и возвращает отформатированный текст.
     *
     * @param  string  $path  Абсолютный путь к файлу (.blade.php или .php)
     * @return string  Отформатированный текст или оригинал при ошибке
     */
    public function formatFile(string $path): string
    {

        // php vendor/bin/pint --no-interaction --quiet --preset laravel tests/test.blade.php        

        $process = new Process(
            [
                base_path('vendor/bin/pint'),
                '--no-interaction',
                '--quiet',
                '--preset',
                'laravel',
                '/dataMagicPro/view/components.blade.php',
            ]
        );

        return '123';

        if (!File::exists($path)) {
            throw new \RuntimeException("Файл не найден: {$path}");
        }


        $dir = dirname($path);
        $name = basename($path);
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $base = basename($name, '.' . $ext);


        // создаём временный файл с таймстампом в начале
        $timestamp = date('Ymd_His');
        $tmpFile = "{$dir}/{$timestamp}_{$base}.{$ext}";
        File::copy($path, $tmpFile);

        // запускаем Pint из корня Laravel, но передаём абсолютный путь
        $process = new Process(
            [
                base_path('vendor/bin/pint'),
                '--no-interaction',
                '--quiet',
                '--preset',
                'laravel',
                $tmpFile,
            ]
        );

        $process->setTimeout(10);
        $process->run();



        $formatted = $process->isSuccessful()
            ? File::get($tmpFile)
            : 'Неудача форматирования Pint---------: ' . File::get($path);

        // всегда удаляем временный файл
        File::delete($tmpFile);

        return $formatted;
    }
}
