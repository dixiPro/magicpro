<?php

namespace MagicProSrc\Console;

use Illuminate\Console\Command;

class InstallMagicProCommand extends Command
{
    protected $signature = 'magicpro:install';
    protected $description = 'Создаёт служебные директории MagicPro';

    public function handle(): int
    {
        $this->info("Начинаем установку MagicPro...\n");

        foreach (MAGIC_DIRECTORIES  as $path) {
            if (!is_dir($path)) {
                mkdir($path, 0775, true);
                $this->info("Создана директория: {$path}");
            } else {
                $this->line("Уже существует: {$path}");
            }
        }

        $this->info("\n✅ Установка MagicPro завершена.\n");
        $this->warn("После установки выполните:");
        $this->warn('sudo chown -R $(logname):www-data ' . MAGIC_DATA_DIR . "\n");
        $this->warn('sudo chown -R $(logname):www-data ' . FILES_UPLOAD_DIRECTORY . "\n");

        return self::SUCCESS;
    }
}
