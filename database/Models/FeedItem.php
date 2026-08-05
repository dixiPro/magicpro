<?php

namespace MagicProDatabaseModels; // в композере прописывается

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * One record of a feed ("Ленты" module).
 *
 * Values live in fixed physical slots, and which logical field sits in which
 * slot is described by the schema of its feed. Application code works with
 * logical names; the translation layer (FeedItemBuilder) is a separate piece
 * and is not part of this model yet.
 */
class FeedItem extends Model implements HasMedia
{
    use InteractsWithMedia;

    /**
     * Number of slots of each type. The same numbers are written out in the
     * migration on purpose: a migration is a frozen record of what the table
     * looked like back then, and it must not change meaning when these
     * constants do.
     */
    public const STRINGS = 5;
    public const BIGINTS = 3;
    public const DATES   = 3;
    public const BOOLS   = 3;
    public const LINKS   = 3;

    protected $table = 'magicPro_feed_items';

    /**
     * Slot columns are added in the constructor.
     *
     * __position stays out of the list: it is counted when the record is
     * created and changed only by a move, so a stray key in an update must not
     * overwrite it. Internal code assigns it directly, bypassing the list.
     */
    protected $fillable = [
        'feed_id',
        '__data',
        '__visible',
    ];

    /**
     * The json column carries no database default — mysql does not allow one —
     * so a new record gets an empty object here.
     */
    protected $attributes = [
        '__data' => '{}',
    ];

    public function __construct(array $attributes = [])
    {
        // The parent constructor fills the attributes, so by the time it runs
        // the list of fillable columns has to be complete.
        $this->fillable = array_merge($this->fillable, self::slotColumns());

        parent::__construct($attributes);
    }

    /** Physical slot columns, in schema order. */
    public static function slotColumns(): array
    {
        $counts = [
            'string' => self::STRINGS,
            'bigint' => self::BIGINTS,
            'date'   => self::DATES,
            'bool'   => self::BOOLS,
            'link'   => self::LINKS,
        ];

        $columns = [];

        foreach ($counts as $type => $count) {
            for ($i = 1; $i <= $count; $i++) {
                $columns[] = '__' . $type . '_' . $i;
            }
        }

        return $columns;
    }

    protected function casts(): array
    {
        $casts = [
            'feed_id'    => 'integer',
            '__data'     => 'array',
            '__position' => 'integer',
            '__visible'  => 'boolean',
        ];

        for ($i = 1; $i <= self::BIGINTS; $i++) {
            $casts['__bigint_' . $i] = 'integer';
        }

        for ($i = 1; $i <= self::DATES; $i++) {
            $casts['__date_' . $i] = 'datetime';
        }

        for ($i = 1; $i <= self::BOOLS; $i++) {
            $casts['__bool_' . $i] = 'boolean';
        }

        for ($i = 1; $i <= self::LINKS; $i++) {
            $casts['__link_' . $i] = 'integer';
        }

        return $casts;
    }

    public function feed(): BelongsTo
    {
        return $this->belongsTo(Feed::class, 'feed_id');
    }

    /**
     * Every query over this table goes through the builder that understands
     * logical field names. Without a feed it simply has no schema to use.
     */
    public function newEloquentBuilder($query): FeedItemBuilder
    {
        return new FeedItemBuilder($query);
    }

    // === logical field names ===

    /**
     * Logical name => physical column, and the codes living inside __data.
     * Null means "not resolved yet", not "the feed has no fields".
     *
     * @var array{0: array<string, string>, 1: array<int, string>}|null
     */
    protected ?array $logicalNames = null;

    /** Which state of the schemas the map above was taken from. */
    protected int $logicalNamesGeneration = -1;

    /** Hands the schema of the feed over, so no extra query is needed. */
    public function useSchema(array $fields, array $dataCodes): static
    {
        $this->logicalNames           = [$fields, $dataCodes];
        $this->logicalNamesGeneration = Feed::schemaGeneration();

        return $this;
    }

    /**
     * Passes the map on as it is, together with the moment it was taken.
     *
     * Re-stamping it here would turn a stale map into a fresh-looking one: a
     * query built before a schema change would keep handing out the old names.
     */
    protected function copySchemaFrom(self $prototype): void
    {
        $this->logicalNames           = $prototype->logicalNames;
        $this->logicalNamesGeneration = $prototype->logicalNamesGeneration;
    }

    /**
     * The schema comes from the feed the record belongs to. Records loaded
     * through Feed::items() already carry it; one built by hand looks its feed
     * up once, and a record without a feed simply has no logical names.
     *
     * feed_id is read out of the raw attributes on purpose — going through
     * getAttribute() here would call this method again.
     */
    protected function logicalNames(): array
    {
        // a map taken before a schema change is read again, not trusted
        if ($this->logicalNames !== null
            && $this->logicalNamesGeneration === Feed::schemaGeneration()
        ) {
            return $this->logicalNames;
        }

        $feedId = $this->attributes['feed_id'] ?? null;

        // Nothing is cached while the feed is unknown: a record built by hand
        // gets its feed_id at some point, and an empty schema remembered now
        // would make every field after that unknown.
        if ($feedId === null) {
            return [[], []];
        }

        $this->logicalNamesGeneration = Feed::schemaGeneration();

        return $this->logicalNames = Feed::schemaMapOf((int) $feedId);
    }

    /**
     * Whether a name is a real column and needs no schema lookup at all.
     *
     * Method names are not checked here on purpose: a field may legitimately
     * be called feed or media, and the schema has to win over a relation of
     * the same name. Anything the schema does not know still falls through to
     * Eloquent, so relations and accessors keep working.
     */
    protected function isPhysicalName(string $key): bool
    {
        return str_starts_with($key, '__')
            || array_key_exists($key, $this->attributes);
    }

    public function getAttribute($key)
    {
        if ($this->isPhysicalName($key)) {
            return parent::getAttribute($key);
        }

        [$fields, $dataCodes] = $this->logicalNames();

        if (isset($fields[$key])) {
            return parent::getAttribute($fields[$key]);
        }

        if (in_array($key, $dataCodes, true)) {
            return ($this->__data ?? [])[$key] ?? null;
        }

        return parent::getAttribute($key);
    }

    public function setAttribute($key, $value)
    {
        if ($this->isPhysicalName($key)) {
            return parent::setAttribute($key, $value);
        }

        [$fields, $dataCodes] = $this->logicalNames();

        if (isset($fields[$key])) {
            return parent::setAttribute($fields[$key], $value);
        }

        if (in_array($key, $dataCodes, true)) {
            // one field of the json changes, the rest of it stays
            $data = $this->__data ?? [];

            $data[$key] = $value;

            return parent::setAttribute('__data', $data);
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * feed_id is filled before everything else: it decides which schema the
     * other names are read with, and an array does not promise any order.
     * Without this, new FeedItem(['name' => …, 'feed_id' => …]) would drop the
     * name — at that moment the record would not know its feed yet.
     */
    public function fill(array $attributes)
    {
        if (array_key_exists('feed_id', $attributes) && $this->isFillable('feed_id')) {
            $this->setAttribute('feed_id', $attributes['feed_id']);

            unset($attributes['feed_id']);
        }

        return parent::fill($attributes);
    }

    /**
     * Logical names are fillable too, otherwise create() and update() would
     * silently drop them: they are not in $fillable, the schema decides.
     *
     * The list itself is extended, not just the isFillable() check: fill()
     * first cuts the incoming array down to getFillable() and only then asks
     * about each key, so a name missing from the list never gets that far.
     */
    public function getFillable()
    {
        [$fields, $dataCodes] = $this->logicalNames();

        return array_merge(parent::getFillable(), array_keys($fields), $dataCodes);
    }

    /**
     * A new record made from this one — by create() or by a relation — starts
     * with the same schema. It is set before the attributes are filled: by
     * then logical names already have to resolve.
     */
    public function newInstance($attributes = [], $exists = false)
    {
        $model = parent::newInstance([], $exists);

        if ($this->logicalNames !== null) {
            $model->copySchemaFrom($this);
        }

        $model->fill((array) $attributes);

        return $model;
    }

    /** A record hydrated from a query keeps the schema of that query. */
    public function newFromBuilder($attributes = [], $connection = null)
    {
        $model = parent::newFromBuilder($attributes, $connection);

        if ($this->logicalNames !== null) {
            $model->copySchemaFrom($this);
        }

        return $model;
    }

    /**
     * The position of a new record is counted here, right before the insert.
     */
    protected static function booted(): void
    {
        static::creating(function (self $item): void {
            if ($item->getAttribute('__position') === null) {
                $item->__position = static::nextPosition((int) $item->feed_id);
            }
        });
    }

    /**
     * An insert always runs inside a transaction, so the lock nextPosition()
     * takes on the feed row still holds when the row actually goes in. That
     * makes every way of creating a record equally safe — through the relation
     * of a feed, or by hand — instead of one blessed method everybody has to
     * remember.
     */
    public function save(array $options = [])
    {
        if ($this->exists) {
            return parent::save($options);
        }

        return DB::transaction(fn () => parent::save($options));
    }

    /**
     * max(__position) + 1 inside the feed. The feed row is locked first: two
     * parallel inserts would otherwise get the same number, and equal
     * positions break pagination — one record shows twice, another nowhere.
     *
     * The lock lives until the surrounding transaction commits, so this is
     * only a real guarantee when the insert runs inside one.
     */
    public static function nextPosition(int $feedId): int
    {
        DB::table('magicPro_feeds')
            ->where('id', $feedId)
            ->lockForUpdate()
            ->first();

        $max = static::query()
            ->where('feed_id', $feedId)
            ->max('__position');

        return (int) $max + 1;
    }

    /**
     * Deletion in the order the spec sets: incoming links first, then the
     * files, then the row.
     *
     * The check sits in delete() itself rather than in a deleting handler.
     * Spatie subscribes to that event as well, and then the order would depend
     * on who subscribed first — it would be possible to wipe the files of a
     * record that turns out to be undeletable. Here the check passes first,
     * and only then parent::delete() lets Spatie remove the files in its own
     * handler, before the row goes.
     *
     * Mass deletes go straight to sql and never reach this method, so a
     * query-builder delete over this table has to run the check itself.
     */
    public function delete(): bool
    {
        return DB::transaction(function (): bool {
            if ($this->hasIncomingLinks()) {
                throw new RuntimeException('На запись ссылаются, она не удаляется');
            }

            return (bool) parent::delete();
        });
    }

    /** Whether any record of any feed still points at this one. */
    public function hasIncomingLinks(): bool
    {
        $query = static::query();

        for ($i = 1; $i <= self::LINKS; $i++) {
            $query->orWhere('__link_' . $i, $this->getKey());
        }

        return $query->exists();
    }
}
