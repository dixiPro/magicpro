<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('magicPro_feed_groups', function (Blueprint $table) {
            $table->id();

            $table->string('title');

            // display order in the admin panel
            $table->bigInteger('position')->default(0);

            $table->timestamps();

            // groups are always read whole and always in order; id closes the
            // index because with equal positions the row order is undefined
            $table->index(['position', 'id']);
        });

        // Default group: new feeds fall into it, it may be renamed but not
        // deleted. The id is not set explicitly — the table is empty, so the
        // row gets id 1 anyway, and the postgres sequence stays in sync
        // (an explicitly inserted id does not advance it).
        DB::table('magicPro_feed_groups')->insert([
            'title'      => 'main',
            'position'   => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('magicPro_feed_groups');
    }
};
