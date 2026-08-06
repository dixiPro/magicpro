<?php

namespace MagicProDatabaseModels; // в композере прописывается

use Closure;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

/**
 * Query builder of a feed record.
 *
 * Application code writes logical field names, the table holds fixed physical
 * slots, and which name sits in which slot depends on the feed. So a query
 * understands logical names only when it knows its feed: Feed::items() hands
 * the schema over, while a bare FeedItem::query() gets none and keeps working
 * with physical and system columns, as internal module code needs.
 */
class FeedItemBuilder extends Builder
{
    /** Columns Laravel owns. They are never logical names. */
    private const SYSTEM = ['id', 'feed_id', 'created_at', 'updated_at'];

    /** Logical code => physical column. */
    protected array $fields = [];

    /** Codes stored inside __data: fine in a record, never in a query. */
    protected array $dataCodes = [];

    /** Whether this query knows its feed at all. */
    protected bool $hasSchema = false;

    public function forFeed(Feed $feed): static
    {
        [$fields, $dataCodes] = $feed->schemaMap();

        return $this->useSchema($fields, $dataCodes);
    }

    public function useSchema(array $fields, array $dataCodes): static
    {
        $this->fields    = $fields;
        $this->dataCodes = $dataCodes;
        $this->hasSchema = true;

        // The model of this query hands the schema on to every record it makes
        // or hydrates. Without it each record would have to look its feed up
        // by itself — a query per row.
        $this->getModel()->useSchema($fields, $dataCodes);

        return $this;
    }

    // === query methods, logical names translated on the way in ===

    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if ($column instanceof Closure && is_null($operator)) {
            // A nested query is created by the model and knows nothing about
            // the feed, so the schema is handed to it here — otherwise logical
            // names would work outside brackets and break inside them.
            return parent::where(function (self $query) use ($column): void {
                if ($this->hasSchema) {
                    $query->useSchema($this->fields, $this->dataCodes);
                }

                $column($query);
            }, $operator, $value, $boolean);
        }

        if (is_array($column)) {
            return parent::where($this->columnsOf($column), $operator, $value, $boolean);
        }

        return parent::where($this->column($column), $operator, $value, $boolean);
    }

    public function whereIn($column, $values, $boolean = 'and', $not = false)
    {
        $this->query->whereIn($this->column($column), $values, $boolean, $not);

        return $this;
    }

    public function whereNotIn($column, $values, $boolean = 'and')
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    public function whereNull($columns, $boolean = 'and', $not = false)
    {
        $this->query->whereNull($this->columnList($columns), $boolean, $not);

        return $this;
    }

    public function whereNotNull($columns, $boolean = 'and')
    {
        return $this->whereNull($columns, $boolean, true);
    }

    public function orderBy($column, $direction = 'asc')
    {
        $this->query->orderBy($this->column($column), $direction);

        return $this;
    }

    public function orderByDesc($column)
    {
        return $this->orderBy($column, 'desc');
    }

    public function value($column)
    {
        return parent::value($this->column($column));
    }

    public function pluck($column, $key = null)
    {
        return parent::pluck(
            $this->column($column),
            $key === null ? null : $this->column($key)
        );
    }

    // === writing ===

    /**
     * Group delete: records go one by one, through the model, so that the rule
     * "a record somebody links to is not deleted" holds here as well, and so
     * that the files go with them. Records still held by links are skipped,
     * not raised as an error — this is what the group delete of the spec does:
     * remove everything nobody holds and report the rest. Returns the number
     * of deleted records.
     *
     * delete() itself is deliberately left as Eloquent wrote it. Deleting a
     * model runs newModelQuery()->delete(), so a builder that deleted through
     * models would call itself without end.
     */
    public function deleteEach(): int
    {
        $deleted = 0;

        foreach ($this->get() as $item) {
            if ($item->hasIncomingLinks()) {
                continue;
            }

            $deleted += (int) $item->delete();
        }

        return $deleted;
    }

    /**
     * A mass update over physical columns is one sql statement. Fields living
     * inside __data cannot be updated that way — the whole json would be
     * overwritten — so as soon as one of them is touched, the records are
     * loaded and saved one by one.
     */
    public function update(array $values)
    {
        [$columns, $dataValues] = $this->splitValues($values);

        if ($dataValues === []) {
            return parent::update($columns);
        }

        $updated = 0;

        foreach ($this->get() as $item) {
            $data = $item->__data ?? [];

            foreach ($dataValues as $code => $value) {
                $data[$code] = $value;
            }

            $item->__data = $data;

            foreach ($columns as $column => $value) {
                $item->setAttribute($column, $value);
            }

            $item->save();
            $updated++;
        }

        return $updated;
    }

    /** Splits an update into physical columns and fields of __data. */
    protected function splitValues(array $values): array
    {
        $columns    = [];
        $dataValues = [];

        foreach ($values as $name => $value) {
            if (is_string($name) && in_array($name, $this->dataCodes, true)) {
                $dataValues[$name] = $value;

                continue;
            }

            $columns[$this->column($name)] = $value;
        }

        return [$columns, $dataValues];
    }

    // === translation ===

    /**
     * Logical name to physical column.
     *
     * An unknown name is an error and never reaches the database: passed
     * through, it would give an sql error at best and hit a wrong column at
     * worst. Anything that is not a string — an expression, a subquery — goes
     * through untouched.
     */
    protected function column(mixed $name): mixed
    {
        if (! is_string($name) || ! $this->hasSchema) {
            return $name;
        }

        // already a real column, or a qualified one like table.id
        if (str_starts_with($name, '__')
            || in_array($name, self::SYSTEM, true)
            || str_contains($name, '.')
        ) {
            return $name;
        }

        if (isset($this->fields[$name])) {
            return $this->fields[$name];
        }

        if (in_array($name, $this->dataCodes, true)) {
            throw new RuntimeException(
                sprintf(\MagicProSrc\MagicLang::getMsg('feed_err_field_in_data'), $name)
            );
        }

        throw new RuntimeException(
            sprintf(\MagicProSrc\MagicLang::getMsg('feed_err_field_no_schema'), $name)
        );
    }

    /** Column names of a where() called with an array of conditions. */
    protected function columnsOf(array $conditions): array
    {
        $translated = [];

        foreach ($conditions as $key => $value) {
            if (is_string($key)) {
                $translated[$this->column($key)] = $value;

                continue;
            }

            // ['name', '=', 'Вася'] — the name is the first element
            if (is_array($value) && isset($value[0])) {
                $value[0] = $this->column($value[0]);
            }

            $translated[$key] = $value;
        }

        return $translated;
    }

    /** whereNull() and whereNotNull() take either one name or a list. */
    protected function columnList(mixed $columns): mixed
    {
        if (! is_array($columns)) {
            return $this->column($columns);
        }

        return array_map(fn ($column) => $this->column($column), $columns);
    }
}
