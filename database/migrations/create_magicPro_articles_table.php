<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        // DB::transaction(function () {});
        // mySql d't support createtabel transaction
        if (!Schema::hasTable('articles')) {
            Schema::create('articles', function (Blueprint $table) {
                $table->id();
                $table->integer('parentId')->default(0)->index();
                $table->integer('npp')->default(0);
                $table->string('name')->index();
                $table->string('title')->default('');
                $table->text('controller')->nullable();
                $table->text('body')->nullable();
                $table->string('templateName')->default('');
                $table->boolean('directory')->default(false);
                $table->boolean('menuOn')->default(false);
                $table->boolean('isRoute')->default(false);
                $table->text('routeParams')->nullable();
                $table->timestamps();
            });

            // Стартовые статьи (root, error404, index) создаёт Installer при
            // заходе в /a_dmin: миграция отрабатывает один раз и молчит, если
            // её вставка пропущена, а проверка нужна на каждой установке.
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
