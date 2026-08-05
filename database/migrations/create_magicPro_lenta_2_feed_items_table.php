<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Number of slots of each type. Fixed by the spec: adding one is a
    // migration and new indexes, the admin panel never creates columns.
    private const STRINGS = 5;
    private const BIGINTS = 3;
    private const DATES   = 3;
    private const BOOLS   = 3;
    private const LINKS   = 3;

    public function up(): void
    {
        Schema::create('magicPro_feed_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('feed_id');

            // Indexed slots: everything that is searched, filtered, sorted or
            // linked. An unused slot stays null, not an empty string — two
            // nulls count as different, two empty strings as equal, and the
            // uniqueness check breaks on that.
            for ($i = 1; $i <= self::STRINGS; $i++) {
                $table->string('__string_' . $i)->nullable();
            }

            for ($i = 1; $i <= self::BIGINTS; $i++) {
                $table->bigInteger('__bigint_' . $i)->nullable();
            }

            for ($i = 1; $i <= self::DATES; $i++) {
                $table->dateTime('__date_' . $i)->nullable();
            }

            // Never null. The default is set here because a field may not be
            // in the schema yet, and the insert still has to pass.
            for ($i = 1; $i <= self::BOOLS; $i++) {
                $table->boolean('__bool_' . $i)->default(false);
            }

            // Id of another feed item, so the same type id() gives: unsigned.
            // No foreign key: "a record somebody links to is not deleted" is a
            // rule of the module, not of the database — see the deletion part
            // of the spec.
            for ($i = 1; $i <= self::LINKS; $i++) {
                $table->unsignedBigInteger('__link_' . $i)->nullable();
            }

            // Everything that is only stored, edited and shown, large texts
            // included. No db-level default: mysql does not allow one on a
            // json column, an empty object comes from the model.
            $table->json('__data');

            // The real value is max(__position) + 1 inside the feed, counted
            // in a transaction when the record is created.
            $table->bigInteger('__position')->default(0);

            // A fresh record is hidden until the editor says otherwise.
            $table->boolean('__visible')->default(false);

            $table->timestamps();

            // --- Indexes, numbered as in the spec ---
            //
            // Names are given explicitly: generated ones like
            // magicPro_feed_items_feed_id___visible___link_1___position_id_index
            // are longer than the 64 characters mysql allows. In postgres index
            // names are unique across the schema, hence the table prefix.
            //
            // Order of columns everywhere: feed_id, then equality, then
            // sorting, then id. A composite index is read left to right, so a
            // skipped column stops the database from reaching the next one.
            // id closes the sorting indexes for correctness, not for speed:
            // with equal positions the row order is undefined, and on a page
            // border one record shows twice while another shows nowhere.

            // 1. main feed list, manual order
            $table->index(['feed_id', '__visible', '__position', 'id'], 'mp_fi_list');

            // 2. list by creation date
            $table->index(['feed_id', '__visible', 'created_at', 'id'], 'mp_fi_created');

            // 3-5. list and filter by dates
            for ($i = 1; $i <= self::DATES; $i++) {
                $table->index(
                    ['feed_id', '__visible', '__date_' . $i, 'id'],
                    'mp_fi_date_' . $i
                );
            }

            // 6-8. all records holding a given link, in order. Sorting is part
            // of the index: without it the rows are sorted in memory.
            for ($i = 1; $i <= self::LINKS; $i++) {
                $table->index(
                    ['feed_id', '__visible', '__link_' . $i, '__position', 'id'],
                    'mp_fi_link_' . $i
                );
            }

            // 9-11. list by a flag, in order
            for ($i = 1; $i <= self::BOOLS; $i++) {
                $table->index(
                    ['feed_id', '__visible', '__bool_' . $i, '__position', 'id'],
                    'mp_fi_bool_' . $i
                );
            }

            // 12-16. finding one record by an exact string: slug, code,
            // document number. __visible is left out on purpose — a record is
            // opened by its address regardless of visibility, otherwise a
            // draft cannot be previewed.
            for ($i = 1; $i <= self::STRINGS; $i++) {
                $table->index(['feed_id', '__string_' . $i], 'mp_fi_str_' . $i);
            }

            // 17-19. the same for numbers
            for ($i = 1; $i <= self::BIGINTS; $i++) {
                $table->index(['feed_id', '__bigint_' . $i], 'mp_fi_int_' . $i);
            }

            // A record cannot exist without a feed. Declared after the indexes
            // so that index 1 covers it — otherwise the database creates a
            // second index on feed_id. Deleting a feed that still has records
            // is refused: the module empties the feed first.
            $table->foreign('feed_id')
                ->references('id')
                ->on('magicPro_feeds')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('magicPro_feed_items');
    }
};
