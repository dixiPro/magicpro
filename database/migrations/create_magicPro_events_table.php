<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('magicPro_events', function (Blueprint $table) {
            $table->id();

            // normalized event key, e.g. mail*user@site.com*registration
            $table->string('key')->unique();

            // when the event expires; null — never
            $table->timestamp('expires_at')->nullable()->index();

            $table->timestamps();
        });

        echo "Table magicPro_events was created:\n";
    }

    public function down(): void
    {
        Schema::dropIfExists('magicPro_events');
    }
};
