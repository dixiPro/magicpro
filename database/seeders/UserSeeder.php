<?php

namespace MagicProDatabase\seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->count(200)->create();

        $this->command?->info(
            'total ' . User::count()
        );
    }
}

// php artisan db:seed --class="MagicProDatabase\seeders\UserSeeder"