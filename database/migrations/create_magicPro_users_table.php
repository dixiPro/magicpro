<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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

        // Администратор создаётся командой magicpro:admin. Здесь его заводить
        // нельзя: миграция запускается и без терминала, и тогда readline()
        // молча отдавала пустые email и пароль.
    }

    public function down(): void
    {
        Schema::dropIfExists('magicPro_users');
    }
};
