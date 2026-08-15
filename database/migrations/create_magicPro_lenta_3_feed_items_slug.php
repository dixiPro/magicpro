<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Адрес записи для SEO.
 *
 * Отдельная миграция, а не правка старой: таблица уже стоит на живых проектах.
 * Имя файла продолжает нумерацию лент — миграции пакета идут по алфавиту, и
 * `lenta_3` обязан выполниться после `lenta_2`, который эту таблицу создаёт.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('magicPro_feed_items', function (Blueprint $table) {
            // Пусто — это NULL, а не пустая строка: два NULL в SQL не равны, и
            // записей без адреса может быть сколько угодно. Существующие записи
            // получают NULL и живут дальше, slug появится при первом сохранении.
            $table->string('__slug')->nullable()->after('__data');

            // Уникальность в пределах ленты, а не всей таблицы: адрес читается
            // вместе с кодом ленты. Индекс он же и поисковый — запись открывают
            // по адресу независимо от видимости, поэтому __visible в нём нет.
            $table->unique(['feed_id', '__slug'], 'mp_fi_slug');
        });
    }

    public function down(): void
    {
        Schema::table('magicPro_feed_items', function (Blueprint $table) {
            $table->dropUnique('mp_fi_slug');
            $table->dropColumn('__slug');
        });
    }
};
