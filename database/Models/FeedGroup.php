<?php

namespace MagicProDatabaseModels; // в композере прописывается

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

/**
 * Group of feeds ("Ленты" module).
 *
 * Feeds are always listed grouped and a feed cannot exist without a group,
 * so the migration creates one group up front. That row is the fallback for
 * new feeds: it can be renamed, but deleting it is refused — see DEFAULT_ID
 * and the deleting hook below.
 */
class FeedGroup extends Model
{
    /** Group created by the migration: used for new feeds, never deleted. */
    public const DEFAULT_ID = 1;

    protected $table = 'magicPro_feed_groups';

    protected $fillable = [
        'title',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    /**
     * Both bans live on the model, not only in the api command: a feed cannot
     * exist without a group, and that must not depend on which code path
     * reached the delete.
     *
     * Mass deletes bypass model events, so a query-builder delete over this
     * table still has to run these checks itself.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $group): void {
            if ((int) $group->id === self::DEFAULT_ID) {
                throw new RuntimeException('Группа по умолчанию не удаляется');
            }

            // feeds are moved to another group or deleted first
            if ($group->feeds()->exists()) {
                throw new RuntimeException('В группе есть ленты, она не удаляется');
            }
        });
    }

    /**
     * Feeds are always shown in the order set in the admin panel, so the
     * ordering belongs to the relation itself. id closes it: with equal
     * positions the row order is otherwise undefined.
     */
    public function feeds(): HasMany
    {
        return $this->hasMany(Feed::class, 'group_id')
            ->orderBy('position')
            ->orderBy('id');
    }
}
