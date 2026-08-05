<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('magicPro_feeds', function (Blueprint $table) {
            $table->id();

            // how the feed is addressed from blades and controllers;
            // the only real unique index of the module
            $table->string('code')->unique();

            $table->string('title');

            // field description. No db-level default: mysql does not allow one
            // on a json column, the empty schema comes from the model
            $table->json('schema');

            $table->foreignId('group_id');

            // position inside the group
            $table->bigInteger('position')->default(0);

            $table->timestamps();

            // admin main screen: groups, and inside each its feeds in order.
            // Declared before the foreign key so that it also covers it —
            // otherwise the database creates a second index on group_id.
            $table->index(['group_id', 'position', 'id']);

            // a feed cannot exist without a group: the database refuses both
            // an unknown group_id and the deletion of a group that still has
            // feeds — the same rule the model checks
            $table->foreign('group_id')
                ->references('id')
                ->on('magicPro_feed_groups')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('magicPro_feeds');
    }
};
