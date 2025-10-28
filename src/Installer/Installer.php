<?php

namespace MagicProSrc;

use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use MagicProSrc\Config\MagicGlobals; // Глобальные константы

class Installer
{
    public static function postInstall(Event $event): void
    {
        $io = $event->getIO();
        $io->write("\n🚀 Запуск установки MagicPro...\n");

        // 1️⃣ Регистрируем глобальные константы
        MagicGlobals::register();

        // 2️⃣ Создаём необходимые директории
        foreach (MAGIC_DIRECTORIES as $path) {
            if (!is_dir($path)) {
                mkdir($path, 0775, true);
                $io->write("📁 Создана директория: {$path}");
            } else {
                $io->write("✔️ Уже существует: {$path}");
            }
        }

        // 3️⃣ Копируем ассеты пакета в public/vendor/magicpro
        $fs = new Filesystem();
        $source = getcwd() . '/vendor/magicpro/magicpro/public';
        $target = getcwd() . '/public/vendor/magicpro';

        try {
            if (is_dir($source)) {
                // Очистить целевую папку, чтобы не было старых файлов
                $fs->remove($target);
                // копируем
                $fs->mirror($source, $target);
                $io->write("✅ Ассеты скопированы из {$source} → {$target}");
            } else {
                $io->write("⚠️ Папка {$source} не найдена, пропускаю копирование ассетов");
            }
        } catch (\Throwable $e) {
            $io->write("❌ Ошибка при копировании ассетов: " . $e->getMessage());
        }

        // 4️⃣ Прогоняем миграции
        try {
            Artisan::call('migrate', ['--force' => true]);
            $io->write("✅ Миграции применены");
        } catch (\Throwable $e) {
            $io->write("⚠️ Не удалось выполнить миграции: " . $e->getMessage());
        }


        // 6️⃣ Финальные инструкции
        $io->write("\n✅ Установка MagicPro завершена.\n");
        $io->write("⚙️ После установки выполните:\n");
        $io->write('sudo chown -R $(logname):www-data ' . MAGIC_DATA_DIR);
        $io->write('');
        $io->write('sudo chown -R $(logname):www-data ' . FILES_UPLOAD_DIRECTORY . "\n");
    }
}
