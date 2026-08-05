<?php

namespace MagicProDatabaseModels; // в композере прописывается

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A feed ("Ленты" module): its code, its group and its schema.
 *
 * The schema describes which logical field lives in which physical column of
 * magicPro_feed_items. It is stored and copied as a whole, so its format
 * version travels inside the json itself.
 */
class Feed extends Model
{
    /** Schema of a feed that has no fields yet. */
    public const EMPTY_SCHEMA = '{"version":1,"fields":[]}';

    protected $table = 'magicPro_feeds';

    protected $fillable = [
        'code',
        'title',
        'schema',
        'group_id',
        'position',
    ];

    /**
     * The json column carries no database default — mysql does not allow one —
     * so a new feed gets its empty schema here. An insert made around the
     * model has to fill the column itself.
     */
    protected $attributes = [
        'schema' => self::EMPTY_SCHEMA,
    ];

    protected function casts(): array
    {
        return [
            'schema'   => 'array',
            'group_id' => 'integer',
            'position' => 'integer',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(FeedGroup::class, 'group_id');
    }

    /**
     * Records of this feed. The query is given the schema, so logical field
     * names work in it: $feed->items()->where('title', 'Сыр').
     */
    public function items(): HasMany
    {
        $relation = $this->hasMany(FeedItem::class, 'feed_id');

        // The query translates logical names in conditions and hands the
        // schema on to its model, so records read and created through this
        // relation understand the same names.
        //
        // Eager loading builds the relation on an empty Feed — the schema of
        // such an instance is the empty default, and handing it over would
        // teach the records that their feed has no fields at all. So it is
        // only passed when there is a real feed behind it; otherwise each
        // record looks its own schema up, and schemaMapOf() keeps that to one
        // query for the whole feed.
        if ($this->exists) {
            $relation->getQuery()->forFeed($this);
        }

        return $relation;
    }

    /**
     * Two lists made out of the schema: logical name => physical column for
     * the slots, and the codes that live inside __data.
     *
     * The __data container itself has no value of its own — only its nested
     * fields do — so its own code is not a field name and is skipped.
     */
    /**
     * Schema maps already read, by feed id. Records loaded without a feed
     * context resolve their names through this, so a page of a hundred rows
     * costs one query and not a hundred.
     *
     * The whole cache is dropped as soon as any feed is saved: schemas change
     * rarely, and a stale map would silently read a field from a wrong slot.
     *
     * @var array<int, array{0: array<string, string>, 1: array<int, string>}>
     */
    protected static array $schemaMaps = [];

    /**
     * Grows every time any feed is saved.
     *
     * Records carry the map they were given, and a map handed out before a
     * schema change is stale. Comparing this number tells them to read it
     * again — clearing the cache alone would not reach the objects that
     * already hold a copy.
     */
    protected static int $schemaGeneration = 0;

    public static function schemaGeneration(): int
    {
        return self::$schemaGeneration;
    }

    protected static function booted(): void
    {
        static::saved(function (): void {
            self::$schemaMaps = [];
            self::$schemaGeneration++;
        });

        static::deleted(function (): void {
            self::$schemaMaps = [];
            self::$schemaGeneration++;
        });
    }

    /** The schema map of a feed by its id, read at most once. */
    public static function schemaMapOf(int $feedId): array
    {
        if (! array_key_exists($feedId, self::$schemaMaps)) {
            $feed = static::find($feedId);

            self::$schemaMaps[$feedId] = $feed === null ? [[], []] : $feed->schemaMap();
        }

        return self::$schemaMaps[$feedId];
    }

    public function schemaMap(): array
    {
        $fields    = [];
        $dataCodes = [];

        foreach ($this->schema['fields'] ?? [] as $field) {
            $column = $field['column'] ?? null;
            $code   = $field['code'] ?? null;

            if ($column === null || $code === null) {
                continue;
            }

            if ($column === '__data') {
                foreach ($field['data'] ?? [] as $nested) {
                    if (isset($nested['code'])) {
                        $dataCodes[] = $nested['code'];
                    }
                }

                continue;
            }

            $fields[$code] = $column;
        }

        return [$fields, $dataCodes];
    }
}
