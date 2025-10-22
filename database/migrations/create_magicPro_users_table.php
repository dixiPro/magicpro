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

        $email = 't@t.t';
        $password = 'mmagic';

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
        echo "-----> Email: " . $email . "\n";
        echo "-----> Password:" . $password . "\n";
    }

    public function down(): void
    {
        Schema::dropIfExists('magicPro_users');
    }
};
