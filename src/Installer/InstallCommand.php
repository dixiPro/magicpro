<?php

namespace MagicProSrc\Installer;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    protected $signature = 'magicpro:install';
    protected $description = 'Installs MagicPro and publishes its resources';


    public function handle(): void
    {
        $this->info('');
        $this->info('⚙️ Starting MagicPro installation...');

        $this->processDirectories(
            MAGIC_DATA_DIR,
            VENDOR_FROM,
            VENDOR_PUBLIC
        );

        $this->info('🎉 MagicPro installation completed.');
        $this->info('');
    }

    private function processDirectories(string $dataDir, string $vendorFrom, string $vendorPublic): void
    {
        // 1. Check/create MAGIC_DATA_DIR
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0775, true);
            $this->info("Directory created: {$dataDir}");
        } else {
            $this->info("Folder already exists: {$dataDir}");
        }

        // 2. Check/clean VENDOR_PUBLIC
        if (!is_dir($vendorPublic)) {
            mkdir($vendorPublic, 0775, true);
            $this->info("Directory created: {$vendorPublic}");
        } else {
            $this->info("Cleaning directory: {$vendorPublic}");
            File::cleanDirectory($vendorPublic);
        }

        // 3. Copy files from VENDOR_FROM to VENDOR_PUBLIC
        $this->info("Copying files from {$vendorFrom} to {$vendorPublic}...");
        File::copyDirectory($vendorFrom, $vendorPublic);
        $this->info('Copy completed.');
        $this->warn('→→→ run: sudo chown -R :www-data ' . MAGIC_DATA_DIR);
    }
}
