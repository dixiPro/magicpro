<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('magicPro_email_addresses', function (Blueprint $table) {
            $table->id();

            // проверка блокировки перед каждой отправкой — самый частый запрос
            $table->string('email')->unique();

            // источник данных может быть не только email, поэтому храним ip
            $table->string('ip_address')->nullable();

            $table->boolean('blocked')->default(false);
            $table->text('block_reason')->nullable();
            $table->timestamp('blocked_at')->nullable();

            $table->timestamps();
        });

        echo "Table magicPro_email_addresses was created:\n";
    }

    public function down(): void
    {
        Schema::dropIfExists('magicPro_email_addresses');
    }
};
