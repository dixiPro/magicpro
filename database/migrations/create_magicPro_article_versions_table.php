<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The first run of this migration died on a MySQL-only statement and
        // left an empty table behind with a binary column. An empty table of
        // this migration is its own leftover and is rebuilt; a table with rows
        // in it is somebody's archive and is left alone.
        if (Schema::hasTable('magicPro_article_versions')) {
            if (DB::table('magicPro_article_versions')->count() > 0) {
                echo "Table magicPro_article_versions is not empty, left as it is:\n";

                return;
            }

            Schema::drop('magicPro_article_versions');
        }

        Schema::create('magicPro_article_versions', function (Blueprint $table) {
            $table->id();

            // who saved it: a person of the admin panel, and the same for МСП —
            // the token carries the id of its owner. Zero means nobody was
            // logged in, a save from the console
            $table->integer('userId')->default(0);

            // whose version this is. The article may be gone by now: versions of
            // a deleted article are the only way back and are kept until they
            // are swept by hand
            $table->integer('articleId')->index();

            // the whole article as json, plain text and not compressed. Text
            // because a binary column travels differently in every driver — on
            // postgres a bytea needs its own writing and its own reading, past
            // Eloquent — and text goes everywhere the same way. Not compressed
            // because there is nothing to compress: the median blade of a site
            // is half a kilobyte, and gzip would buy tens of megabytes in the
            // worst case while taking away reading a version by eye and looking
            // for one with a plain `like`
            $table->longText('data');

            // written once and never touched again, so there is no updated_at
            $table->timestamp('created_at')->nullable();
        });

        echo "Table magicPro_article_versions was created:\n";
    }

    public function down(): void
    {
        Schema::dropIfExists('magicPro_article_versions');
    }
};
