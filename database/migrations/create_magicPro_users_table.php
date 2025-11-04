<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('magicPro_users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role', 50)->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        $email = readline('Введите email администратора: ');
        $password = readline('Введите пароль администратора: ');

        // создаём администратора
        DB::table('magicPro_users')->insert([
            'name'       => 'Admin',
            'email'      => $email,
            'password'   => Hash::make($password),
            'role'       => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "Table magicPro_users was created:\n";
        echo "Admin  created:\n";
        echo "\033[32m-----> Email: " . $email  . "\033[0m\n" . "\n";
        echo "\033[32m-----> Password:" . $password . "\033[0m\n" . "\n";
    }

    public function down(): void
    {
        Schema::dropIfExists('magicPro_users');
    }
};
