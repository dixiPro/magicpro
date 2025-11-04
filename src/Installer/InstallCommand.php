<?php

namespace MagicProSrc\Installer;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    protected $signature = 'magicpro:install';
    protected $description = 'Устанавливает MagicPro и публикует его ресурсы';


    public function handle(): void
    {
        $this->info('');
        $this->info('⚙️ Начало установки MagicPro...');

        $this->processDirectories(
            MAGIC_DATA_DIR,
            VENDOR_FROM,
            VENDOR_PUBLIC
        );

        $this->info('🎉 Установка MagicPro завершена.');
        $this->info('');
    }

    private function processDirectories(string $dataDir, string $vendorFrom, string $vendorPublic): void
    {
        // 1. Проверяем/создаём MAGIC_DATA_DIR
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0775, true);
            $this->info("Создана директория: {$dataDir}");
        } else {
            $this->info("Папка уже существует: {$dataDir}");
        }

        // 2. Проверяем/очищаем VENDOR_PUBLIC
        if (!is_dir($vendorPublic)) {
            mkdir($vendorPublic, 0775, true);
            $this->info("Создана директория: {$vendorPublic}");
        } else {
            $this->info("Очищаем директорию: {$vendorPublic}");
            File::cleanDirectory($vendorPublic);
        }

        // 3. Копируем файлы из VENDOR_FROM в VENDOR_PUBLIC
        $this->info("Копирование файлов из {$vendorFrom} в {$vendorPublic}...");
        File::copyDirectory($vendorFrom, $vendorPublic);
        $this->info('Копирование завершено.');
        $this->warn('→→→ выполните  sudo chown -R :www-data ' . MAGIC_DATA_DIR);
    }
}
