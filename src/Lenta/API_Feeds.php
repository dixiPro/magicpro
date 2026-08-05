<?php

namespace MagicProSrc\Lenta;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use MagicProDatabaseModels\Feed;
use MagicProDatabaseModels\FeedItem;
use MagicProDatabaseModels\FeedGroup;

/**
 * Commands of the feeds api, after the example of MagicProSrc\Mail\API_Mail.
 *
 * Every method works "until the first error": as soon as something is wrong it
 * throws, and the parent (AbstractFeedApi::run) turns that into the negative
 * answer status / errorMsg / data / request.
 *
 * One command, one action. A command that creates an empty group creates it and
 * nothing else; renaming is a separate call.
 */
class API_Feeds extends AbstractFeedApi
{
    /**
     * Centralized error messages, so that the admin panel shows them
     * consistently. Dynamic details are appended at the throw site.
     */
    protected const ERRORS = [
        'group_id_required' => 'Не передан id группы',
        'group_not_found'   => 'Группа не найдена',
        'title_required'    => 'Название группы не может быть пустым',
        'position_required' => 'Не передана позиция',
        'feed_id_required'  => 'Не передан id ленты',
        'feed_not_found'    => 'Лента не найдена',
        'feed_title_empty'  => 'Название ленты не может быть пустым',
        'code_empty'        => 'Код ленты не может быть пустым',
        'code_format'       => 'Код ленты: латинские буквы, цифры, дефис и подчёркивание',
        'code_taken'        => 'Лента с таким кодом уже есть',

        'schema_required'   => 'Не передана схема',
        'fields_required'   => 'В схеме нет массива fields',
        'field_not_object'  => 'Поле схемы должно быть объектом',
        'column_unknown'    => 'Неизвестная колонка поля',
        'column_taken'      => 'Колонка занята другим полем',
        'type_mismatch'     => 'Тип не подходит колонке',
        'editor_unknown'    => 'Неизвестный редактор',
        'data_type_unknown' => 'Неизвестный тип поля в __data',
        'data_unique'       => 'unique нельзя для поля в __data',
        'code_field_empty'  => 'У %s нет code',
        'label_empty'       => 'У %s нет label',
        'code_field_format' => 'Code поля: латинские буквы, цифры, дефис и подчёркивание',
        'code_reserved'     => 'Такое имя занято системой',
        'code_field_taken'  => 'Два поля с одним code',
        'relation_required' => 'У поля связи нет relation',
        'relation_feed'     => 'Лента связи не найдена',
        'relation_column'   => 'Колонка display_code в целевой ленте не занята полем',
        'code_locked'       => 'В ленте есть записи, переименовать поле нельзя',

        'item_id_required'  => 'Не передан id записи',
        'item_not_found'    => 'Запись не найдена',
        'fields_object'     => 'fields должен быть объектом «поле: значение»',
        'field_unknown'     => 'Поля нет в схеме ленты',
        'value_taken'       => 'Такое значение уже есть в ленте',
        'link_not_found'    => 'Связанная запись не найдена',
        'link_wrong_feed'   => 'Связанная запись из другой ленты',
        'link_cycle'        => 'Связи замыкаются в кольцо',
        'filter_shape'      => 'Условие фильтра: field, op, value',
        'filter_op'         => 'Операция фильтра — только like или =',
        'lookup_column'     => 'Колонка не занята полем этой ленты',
        'file_required'     => 'Не передан файл',
        'file_not_image'    => 'Файл не изображение',
        'field_not_image'   => 'Поле не типа image',
    ];

    /** Records per page when the caller did not say. */
    protected const PER_PAGE = 20;

    /** Longest dropdown of a link field. */
    protected const LOOKUP_MAX = 20;

    /** Which type belongs to which kind of slot. */
    protected const COLUMN_TYPES = [
        'string' => 'string',
        'bigint' => 'integer',
        'date'   => 'datetime',
        'bool'   => 'boolean',
        'link'   => 'link',
    ];

    /** Types allowed inside __data — everything that is only stored and shown. */
    protected const DATA_TYPES = [
        'string', 'text', 'integer', 'decimal', 'boolean', 'datetime', 'json', 'code', 'image',
    ];

    /** Editors of a text field. */
    protected const EDITORS = ['html', 'markdown', 'plain'];

    /** Column names Laravel brings; the rest of ours all start with __. */
    protected const RESERVED = ['id', 'feed_id', 'created_at', 'updated_at'];

    /** Title a new group starts with; the operator renames it right away. */
    protected const NEW_GROUP_TITLE = 'Новая группа';

    /** The same for a feed. Its code is generated, since it has to be unique. */
    protected const NEW_FEED_TITLE = 'Новая лента';

    protected array $map = [
        'groupsList'  => 'groupsList',
        'groupCreate' => 'groupCreate',
        'groupSave'   => 'groupSave',
        'groupMove'   => 'groupMove',
        'groupDelete' => 'groupDelete',

        'feedsList'   => 'feedsList',
        'feedGet'     => 'feedGet',
        'feedCreate'  => 'feedCreate',
        'feedSave'    => 'feedSave',
        'feedMove'    => 'feedMove',
        'feedClear'   => 'feedClear',
        'feedDelete'  => 'feedDelete',

        'schemaGet'   => 'schemaGet',
        'schemaSave'  => 'schemaSave',

        'itemsList'   => 'itemsList',
        'itemGet'     => 'itemGet',
        'itemCreate'  => 'itemCreate',
        'itemSave'    => 'itemSave',
        'itemMove'    => 'itemMove',
        'itemDelete'  => 'itemDelete',
        'itemLinks'   => 'itemLinks',
        'itemsLookup' => 'itemsLookup',

        'imageUpload' => 'imageUpload',
        'imageDelete' => 'imageDelete',

        'mdToHtml'    => 'mdToHtml',
    ];

    // === Группы ===

    /**
     * All groups in the order they are shown. id closes the sorting: with equal
     * positions the row order is otherwise undefined.
     */
    protected function groupsList(array $params): array
    {
        return FeedGroup::orderBy('position')
            ->orderBy('id')
            ->get(['id', 'title', 'position'])
            ->toArray();
    }

    /** An empty group at the end of the list. */
    protected function groupCreate(array $params): array
    {
        $group = FeedGroup::create([
            'title'    => self::NEW_GROUP_TITLE,
            'position' => (int) FeedGroup::max('position') + 1,
        ]);

        return $group->only(['id', 'title', 'position']);
    }

    /** Renames a group. Nothing else of a group is editable. */
    protected function groupSave(array $params): array
    {
        $group = $this->group($params);

        $title = trim((string) ($params['title'] ?? ''));

        if ($title === '') {
            throw new Exception(self::ERRORS['title_required']);
        }

        $group->title = $title;
        $group->save();

        return $group->only(['id', 'title', 'position']);
    }

    /**
     * Moves a group to the given place in the list.
     *
     * The whole list is renumbered from zero afterwards: gaps and repeated
     * positions do not break anything visually, but then no one can say what
     * "position 3" means, and the next move lands in the wrong spot.
     */
    protected function groupMove(array $params): array
    {
        $group = $this->group($params);

        if (! isset($params['position'])) {
            throw new Exception(self::ERRORS['position_required']);
        }

        $target = max(0, (int) $params['position']);

        return DB::transaction(function () use ($group, $target): array {
            $ids = FeedGroup::orderBy('position')
                ->orderBy('id')
                ->pluck('id')
                ->reject(fn ($id) => (int) $id === (int) $group->id)
                ->values()
                ->all();

            array_splice($ids, min($target, count($ids)), 0, [$group->id]);

            foreach ($ids as $position => $id) {
                FeedGroup::whereKey($id)->update(['position' => $position]);
            }

            return [
                'id'       => (int) $group->id,
                'position' => (int) array_search((int) $group->id, array_map('intval', $ids), true),
            ];
        });
    }

    /**
     * Deletes a group. The default group and a group that still holds feeds are
     * refused by the model itself, whichever way the delete was reached.
     */
    protected function groupDelete(array $params): bool
    {
        $this->group($params)->delete();

        return true;
    }

    // === Ленты ===

    /**
     * Flat list of feeds. Without groupId — all of them, in the order of the
     * admin screen: groups first, feeds inside a group by their position.
     */
    protected function feedsList(array $params): array
    {
        $query = Feed::withCount('items')
            ->orderBy('group_id')
            ->orderBy('position')
            ->orderBy('id');

        if (isset($params['groupId'])) {
            $query->where('group_id', (int) $params['groupId']);
        }

        return $query->get()
            ->map(fn (Feed $feed) => [
                'id'         => (int) $feed->id,
                'code'       => $feed->code,
                'title'      => $feed->title,
                'groupId'    => (int) $feed->group_id,
                'position'   => (int) $feed->position,
                'itemsCount' => (int) $feed->items_count,
            ])
            ->all();
    }

    /** One feed with its schema. Looked up by id or by code. */
    protected function feedGet(array $params): array
    {
        return $this->feed($params)->only([
            'id', 'code', 'title', 'schema', 'group_id', 'position',
        ]);
    }

    /**
     * An empty feed at the end of its group.
     *
     * The code is generated: it is unique in the table, and a new feed has to
     * be saveable before the operator has thought of a name. The schema comes
     * empty from the model.
     */
    protected function feedCreate(array $params): array
    {
        $group = $this->group(['id' => $params['groupId'] ?? 0]);

        $feed = Feed::create([
            'code'     => 'feed_' . (int) (microtime(true) * 1000),
            'title'    => self::NEW_FEED_TITLE,
            'group_id' => $group->id,
            'position' => (int) Feed::where('group_id', $group->id)->max('position') + 1,
        ]);

        return $feed->only(['id', 'code', 'title', 'group_id', 'position']);
    }

    /**
     * Code, title and group of a feed. The schema is not touched here — it is
     * saved as a whole by schemaSave, with its own checks.
     *
     * Only the keys that came are applied: the admin panel edits these fields
     * on different screens.
     */
    protected function feedSave(array $params): array
    {
        $feed = $this->feed($params);

        if (array_key_exists('code', $params)) {
            $feed->code = $this->code((string) $params['code'], (int) $feed->id);
        }

        if (array_key_exists('title', $params)) {
            $title = trim((string) $params['title']);

            if ($title === '') {
                throw new Exception(self::ERRORS['feed_title_empty']);
            }

            $feed->title = $title;
        }

        if (array_key_exists('groupId', $params)) {
            $feed->group_id = $this->group(['id' => $params['groupId']])->id;
        }

        $feed->save();

        return $feed->only(['id', 'code', 'title', 'group_id', 'position']);
    }

    /**
     * Moves a feed inside its group or into another one. Both the group it
     * leaves and the group it joins are renumbered from zero — see groupMove
     * for why the numbering is kept tight.
     */
    protected function feedMove(array $params): array
    {
        $feed = $this->feed($params);

        if (! isset($params['position'])) {
            throw new Exception(self::ERRORS['position_required']);
        }

        $target = max(0, (int) $params['position']);

        $groupId = isset($params['groupId'])
            ? (int) $this->group(['id' => $params['groupId']])->id
            : (int) $feed->group_id;

        return DB::transaction(function () use ($feed, $groupId, $target): array {
            $leftGroupId = (int) $feed->group_id;

            $feed->group_id = $groupId;
            $feed->save();

            $ids = Feed::where('group_id', $groupId)
                ->orderBy('position')
                ->orderBy('id')
                ->pluck('id')
                ->reject(fn ($id) => (int) $id === (int) $feed->id)
                ->values()
                ->all();

            array_splice($ids, min($target, count($ids)), 0, [$feed->id]);

            $this->renumber($ids);

            // the group it came from has a hole in its numbering now
            if ($leftGroupId !== $groupId) {
                $this->renumber(
                    Feed::where('group_id', $leftGroupId)
                        ->orderBy('position')
                        ->orderBy('id')
                        ->pluck('id')
                        ->all()
                );
            }

            return [
                'id'       => (int) $feed->id,
                'groupId'  => $groupId,
                'position' => (int) array_search((int) $feed->id, array_map('intval', $ids), true),
            ];
        });
    }

    /**
     * Empties a feed, the feed itself stays.
     *
     * Records go in passes: every pass deletes what nobody links to, and the
     * next pass can then reach what those records were holding. When a pass
     * deletes nothing, everything that could go is gone.
     *
     * Returns true when the feed is empty afterwards, false when something is
     * still held by links from elsewhere.
     */
    protected function feedClear(array $params): bool
    {
        $feed = $this->feed($params);

        while ($feed->items()->deleteEach() > 0) {
            // repeat: a deleted record may have been the last one holding another
        }

        return $feed->items()->count() === 0;
    }

    /**
     * The same as feedClear, plus the last step: with no records left the feed
     * description goes too. If something survived, the feed stays with it.
     */
    protected function feedDelete(array $params): bool
    {
        $feed = $this->feed($params);

        while ($feed->items()->deleteEach() > 0) {
            // see feedClear
        }

        if ($feed->items()->count() > 0) {
            return false;
        }

        $feed->delete();

        return true;
    }

    // === Схема ===

    /** The schema of a feed as it is stored. */
    protected function schemaGet(array $params): array
    {
        return $this->feed(['id' => $params['feedId'] ?? 0])->schema ?? [];
    }

    /**
     * Saves the schema as a whole, with all the checks.
     *
     * As a whole and not field by field on purpose: "this column is taken
     * exactly once" and "the code is unique" only mean something on the
     * complete set.
     */
    protected function schemaSave(array $params): array
    {
        $feed = $this->feed(['id' => $params['feedId'] ?? 0]);

        $schema = $params['schema'] ?? null;

        if (! is_array($schema)) {
            throw new Exception(self::ERRORS['schema_required']);
        }

        $feed->schema = $this->checkSchema($schema, $feed);
        $feed->save();

        return $feed->schema;
    }

    /**
     * Checks a schema and gives it back ready to store.
     *
     * Anything wrong stops the save with a human text: which field, what is
     * wrong, what came. A half-saved schema would leave the feed unreadable —
     * its records are only meaningful through it.
     */
    protected function checkSchema(array $schema, Feed $feed): array
    {
        $fields = $schema['fields'] ?? null;

        if (! is_array($fields)) {
            throw new Exception(self::ERRORS['fields_required']);
        }

        $slots = array_merge(FeedItem::slotColumns(), ['__data']);

        $takenColumns = [];
        $takenCodes   = [];

        // column => code, to compare with what the feed had before
        $codeByColumn = [];

        foreach ($fields as $index => $field) {
            if (! is_array($field)) {
                throw new Exception(self::ERRORS['field_not_object'] . ': ' . $index);
            }

            $column = (string) ($field['column'] ?? '');
            $code   = (string) ($field['code'] ?? '');

            $this->checkCode($code, $takenCodes, $column !== '' ? $column : 'поля схемы');

            if (! in_array($column, $slots, true)) {
                throw new Exception(self::ERRORS['column_unknown'] . ': ' . ($column ?: $code));
            }

            if (in_array($column, $takenColumns, true)) {
                throw new Exception(self::ERRORS['column_taken'] . ': ' . $column);
            }

            $takenColumns[] = $column;

            if ($column === '__data') {
                // у контейнера нет ни значения, ни подписи: оператор видит не
                // его, а вложенные поля
                $this->checkDataFields($field['data'] ?? [], $takenCodes);

                // the container itself has no value, so nothing else applies
                continue;
            }

            $this->checkLabel($field, $column);
            $this->checkSlotField($field, $column, $code);

            $codeByColumn[$column] = $code;
        }

        $this->checkCodesKept($feed, $codeByColumn);

        return [
            'version' => (int) ($schema['version'] ?? 1),
            'fields'  => array_values($fields),
        ];
    }

    /**
     * Once a feed holds records, a field keeps the code it was given.
     *
     * The code is how the application reads the value: renaming it silently
     * turns every blade and controller that used the old name into an error,
     * while the values themselves stay where they were.
     *
     * Compared by column: the slot is what actually holds the data, so a field
     * that stays on __string_1 has to keep its name. Fields inside __data are
     * not checked — there the code is the identity of the value, and a rename
     * cannot be told apart from removing one field and adding another.
     */
    protected function checkCodesKept(Feed $feed, array $codeByColumn): void
    {
        if ($feed->items()->count() === 0) {
            return;
        }

        foreach ($feed->schema['fields'] ?? [] as $old) {
            $column = (string) ($old['column'] ?? '');
            $code   = (string) ($old['code'] ?? '');

            if ($column === '' || $column === '__data' || $code === '') {
                continue;
            }

            // the column may be freed altogether, that is not a rename
            if (! isset($codeByColumn[$column]) || $codeByColumn[$column] === $code) {
                continue;
            }

            throw new Exception(
                self::ERRORS['code_locked'] . ": {$column}, было {$code}, пришло {$codeByColumn[$column]}"
            );
        }
    }

    /** A field sitting in a physical slot. */
    protected function checkSlotField(array $field, string $column, string $code): void
    {
        // __string_2 -> string, __link_1 -> link
        $kind = explode('_', substr($column, 2))[0];
        $type = self::COLUMN_TYPES[$kind];

        if (isset($field['type']) && $field['type'] !== $type) {
            throw new Exception(
                self::ERRORS['type_mismatch'] . ": {$code}, {$column} — {$type}, пришло {$field['type']}"
            );
        }

        if (isset($field['editor']) && ! in_array($field['editor'], self::EDITORS, true)) {
            throw new Exception(self::ERRORS['editor_unknown'] . ": {$code}, {$field['editor']}");
        }

        if ($type === 'link') {
            $this->checkRelation($field['relation'] ?? null, $code);
        }
    }

    /**
     * Fields inside __data. They differ from the others only in carrying a type
     * instead of a column — and in what they are not allowed to do: no search,
     * no sorting, no uniqueness, no links.
     */
    protected function checkDataFields(array $dataFields, array &$takenCodes): void
    {
        foreach ($dataFields as $index => $field) {
            if (! is_array($field)) {
                throw new Exception(self::ERRORS['field_not_object'] . ': __data');
            }

            $code = (string) ($field['code'] ?? '');

            $this->checkCode($code, $takenCodes, '__data #' . ((int) $index + 1));
            $this->checkLabel($field, '__data ' . $code);

            $type = (string) ($field['type'] ?? '');

            if (! in_array($type, self::DATA_TYPES, true)) {
                throw new Exception(self::ERRORS['data_type_unknown'] . ": {$code}, {$type}");
            }

            if (isset($field['editor']) && ! in_array($field['editor'], self::EDITORS, true)) {
                throw new Exception(self::ERRORS['editor_unknown'] . ": {$code}, {$field['editor']}");
            }

            if (! empty($field['unique'])) {
                throw new Exception(self::ERRORS['data_unique'] . ': ' . $code);
            }
        }
    }

    /**
     * A code has to be readable in a query and never look like a real column:
     * every column of the module starts with __, and four more names come from
     * Laravel itself.
     */
    protected function checkCode(string $code, array &$takenCodes, string $where): void
    {
        if ($code === '') {
            throw new Exception(sprintf(self::ERRORS['code_field_empty'], $where));
        }

        if (! preg_match('/^[A-Za-z0-9_-]+$/', $code)) {
            throw new Exception(self::ERRORS['code_field_format'] . ': ' . $code);
        }

        if (str_starts_with($code, '__') || in_array($code, self::RESERVED, true)) {
            throw new Exception(self::ERRORS['code_reserved'] . ': ' . $code);
        }

        if (in_array($code, $takenCodes, true)) {
            throw new Exception(self::ERRORS['code_field_taken'] . ': ' . $code);
        }

        $takenCodes[] = $code;
    }

    /**
     * Подпись поля для админки. Без неё оператор видит в форме голый code.
     *
     * В ошибке называется колонка, а не code: строку в таблице оператор узнаёт
     * по слоту, и code у неё может быть уже заполнен.
     */
    protected function checkLabel(array $field, string $where): void
    {
        if (trim((string) ($field['label'] ?? '')) === '') {
            throw new Exception(sprintf(self::ERRORS['label_empty'], $where));
        }
    }

    /** The target of a link: an existing feed and a column occupied there. */
    protected function checkRelation(mixed $relation, string $code): void
    {
        if (! is_array($relation)) {
            throw new Exception(self::ERRORS['relation_required'] . ': ' . $code);
        }

        $target = Feed::find((int) ($relation['feed_id'] ?? 0));

        if (! $target) {
            throw new Exception(self::ERRORS['relation_feed'] . ": {$code}, " . ($relation['feed_id'] ?? ''));
        }

        $displayColumn = (string) ($relation['display_code'] ?? '');

        // a column, not a code: the admin panel hides logical names, so the
        // link does not depend on how a field was named or renamed
        $occupied = in_array($displayColumn, $target->schemaMap()[0], true);

        if (! $occupied) {
            throw new Exception(self::ERRORS['relation_column'] . ": {$code}, {$displayColumn}");
        }
    }

    // === Записи ===

    /**
     * Records of a feed: filter, sorting, pagination.
     *
     * The sorting always ends with id — with equal values the row order is
     * undefined, and on a page border one record would show twice while
     * another shows nowhere.
     */
    protected function itemsList(array $params): array
    {
        $feed  = $this->feed(['id' => $params['feedId'] ?? 0]);
        $query = $feed->items();

        foreach ($params['filter'] ?? [] as $condition) {
            $this->applyFilter($query, $condition);
        }

        $direction = strtolower((string) ($params['direction'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

        $query->orderBy((string) ($params['orderBy'] ?? '__position'), $direction)
            ->orderBy('id', $direction);

        $perPage = max(1, (int) ($params['perPage'] ?? self::PER_PAGE));
        $page    = max(1, (int) ($params['page'] ?? 1));

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'items'    => array_map(
                fn (FeedItem $item) => $this->present($item, $feed),
                $paginator->items()
            ),
            'page'     => $paginator->currentPage(),
            'perPage'  => $perPage,
            'total'    => $paginator->total(),
            'lastPage' => $paginator->lastPage(),
        ];
    }

    /** One record with all its fields. */
    protected function itemGet(array $params): array
    {
        $item = $this->item($params);

        return $this->present($item, $item->feed);
    }

    /**
     * A new record at the end of the feed.
     *
     * It starts hidden and with the defaults from the schema: the position and
     * the visibility are set by the model itself, the same way for every path
     * that creates a record.
     */
    protected function itemCreate(array $params): array
    {
        $feed = $this->feed(['id' => $params['feedId'] ?? 0]);

        $values = [];

        foreach ($this->schemaFields($feed) as $code => $field) {
            if (array_key_exists('default', $field) && $field['default'] !== null) {
                $values[$code] = $field['default'];
            }
        }

        $item = $feed->items()->create($values);

        return $this->present($item->refresh(), $feed);
    }

    /**
     * Saves a record whole: every field of the form comes back, changed or not.
     * There is one operator, nothing to save on.
     *
     * visible is a separate parameter — it is a system column, not a field of
     * the feed. The position is not touched here, itemMove does that.
     *
     * Checks and write share a transaction with the feed row locked: two
     * parallel saves would otherwise both pass a uniqueness check and both
     * write the same value.
     */
    protected function itemSave(array $params): array
    {
        $item = $this->item($params);
        $feed = $item->feed;

        $fields = $params['fields'] ?? [];

        if (! is_array($fields)) {
            throw new Exception(self::ERRORS['fields_object']);
        }

        return DB::transaction(function () use ($item, $feed, $fields, $params): array {
            DB::table('magicPro_feeds')->where('id', $feed->id)->lockForUpdate()->first();

            $known = $this->schemaFields($feed);

            $unknown = array_diff(array_keys($fields), array_keys($known));

            if ($unknown !== []) {
                throw new Exception(self::ERRORS['field_unknown'] . ': ' . implode(', ', $unknown));
            }

            $this->checkValues($feed, $item, $fields, $known);

            foreach ($fields as $code => $value) {
                $item->$code = $value;
            }

            if (array_key_exists('visible', $params)) {
                $item->__visible = (bool) $params['visible'];
            }

            $item->save();

            return $this->present($item->refresh(), $feed);
        });
    }

    /**
     * Moves a record inside its feed. The whole feed is renumbered from zero,
     * for the same reason groups and feeds are.
     */
    protected function itemMove(array $params): array
    {
        $item = $this->item($params);

        if (! isset($params['position'])) {
            throw new Exception(self::ERRORS['position_required']);
        }

        $target = max(0, (int) $params['position']);
        $feed   = $item->feed;

        return DB::transaction(function () use ($item, $feed, $target): array {
            $ids = $feed->items()
                ->orderBy('__position')
                ->orderBy('id')
                ->pluck('id')
                ->reject(fn ($id) => (int) $id === (int) $item->id)
                ->values()
                ->all();

            array_splice($ids, min($target, count($ids)), 0, [$item->id]);

            foreach ($ids as $position => $id) {
                FeedItem::whereKey($id)->update(['__position' => $position]);
            }

            return [
                'id'       => (int) $item->id,
                'position' => (int) array_search((int) $item->id, array_map('intval', $ids), true),
            ];
        });
    }

    /**
     * Deletes one record.
     *
     * false means somebody still links to it — that is an ordinary answer, not
     * an error: the admin panel then reads the holders with itemLinks and opens
     * the recursive dialog.
     */
    protected function itemDelete(array $params): bool
    {
        $item = $this->item($params);

        if ($item->hasIncomingLinks()) {
            return false;
        }

        $item->delete();

        return true;
    }

    /** Who links to this record — the list behind the recursive delete dialog. */
    protected function itemLinks(array $params): array
    {
        $item = $this->item($params);

        $query = FeedItem::query();

        for ($i = 1; $i <= FeedItem::LINKS; $i++) {
            $query->orWhere('__link_' . $i, $item->getKey());
        }

        return $query->orderBy('feed_id')
            ->orderBy('__position')
            ->get()
            ->map(fn (FeedItem $holder) => [
                'id'        => (int) $holder->id,
                'feedId'    => (int) $holder->feed_id,
                'feedTitle' => $holder->feed?->title,
                'title'     => $this->firstValue($holder),
            ])
            ->all();
    }

    /**
     * Records of a feed for the dropdown of a link field.
     *
     * The column is the physical one whose value is shown to the operator — the
     * same that sits in display_code of the link field.
     */
    protected function itemsLookup(array $params): array
    {
        $feed   = $this->feed(['id' => $params['feedId'] ?? 0]);
        $column = (string) ($params['column'] ?? '');

        if (! in_array($column, $feed->schemaMap()[0], true)) {
            throw new Exception(self::ERRORS['lookup_column'] . ': ' . $column);
        }

        $search = trim((string) ($params['search'] ?? ''));
        $limit  = min(self::LOOKUP_MAX, max(1, (int) ($params['limit'] ?? self::LOOKUP_MAX)));

        $query = FeedItem::query()->where('feed_id', $feed->id);

        if ($search !== '') {
            $query->where($column, 'like', '%' . $search . '%');
        }

        return $query->orderBy($column)
            ->limit($limit)
            ->get(['id', $column])
            ->map(fn (FeedItem $item) => [
                'id'    => (int) $item->id,
                'value' => $item->$column,
            ])
            ->all();
    }

    // === Медиа ===

    /**
     * Puts a file into a field of a record and writes its metadata into __data.
     *
     * One field holds one image: the collection is cleared first, so the old
     * file goes with it. What the operator typed — alt and the like — survives,
     * only the file properties are overwritten.
     */
    protected function imageUpload(array $params): array
    {
        $item = $this->item($params);
        $code = $this->imageField($item->feed, (string) ($params['code'] ?? ''));
        $file = $params['file'] ?? null;

        if (! $file instanceof UploadedFile) {
            throw new Exception(self::ERRORS['file_required']);
        }

        if (! str_starts_with((string) $file->getMimeType(), 'image/')) {
            throw new Exception(self::ERRORS['file_not_image'] . ': ' . $file->getMimeType());
        }

        $item->clearMediaCollection($code);

        $media = $item->addMedia($file)->toMediaCollection($code);

        [$width, $height] = getimagesize($media->getPath());

        $data = $item->__data ?? [];

        $data[$code] = array_merge($data[$code] ?? [], [
            'url'  => $media->getUrl(),
            'size' => $media->size,
            'mime' => $media->mime_type,
            'x'    => $width,
            'y'    => $height,
        ]);

        $item->__data = $data;
        $item->save();

        return $data[$code];
    }

    /**
     * Removes the file of a field and its metadata.
     *
     * Both, and in this order: the file lives in the media library, the record
     * only knows about it through __data, and one without the other is either a
     * broken link or a file nobody references.
     */
    protected function imageDelete(array $params): bool
    {
        $item = $this->item($params);
        $code = $this->imageField($item->feed, (string) ($params['code'] ?? ''));

        $item->clearMediaCollection($code);

        $data = $item->__data ?? [];

        unset($data[$code]);

        $item->__data = $data;
        $item->save();

        return true;
    }

    /**
     * Markdown to html, for the preview under the editor.
     *
     * The work itself is MproHelper::mdToHtml, the same call the site makes when
     * it renders the record. Kept that way on purpose: a preview that converted
     * the text on its own would sooner or later disagree with the page.
     */
    protected function mdToHtml(array $params): array
    {
        return ['html' => \MproHelper::mdToHtml((string) ($params['md'] ?? ''))];
    }

    /** The field has to exist in the schema and be an image. */
    protected function imageField(?Feed $feed, string $code): string
    {
        $field = $feed ? ($this->schemaFields($feed)[$code] ?? null) : null;

        if (! $field) {
            throw new Exception(self::ERRORS['field_unknown'] . ': ' . $code);
        }

        if (($field['type'] ?? '') !== 'image') {
            throw new Exception(self::ERRORS['field_not_image'] . ': ' . $code);
        }

        return $code;
    }

    // === Записи, вспомогательное ===

    /** One condition of the itemsList filter. */
    protected function applyFilter($query, mixed $condition): void
    {
        if (! is_array($condition) || ! isset($condition['field'], $condition['op'])) {
            throw new Exception(self::ERRORS['filter_shape']);
        }

        $field = (string) $condition['field'];
        $value = $condition['value'] ?? null;

        // fields of __data and unknown names are refused by the builder itself
        match ((string) $condition['op']) {
            'like'  => $query->where($field, 'like', '%' . $value . '%'),
            '='     => $query->where($field, $value),
            default => throw new Exception(self::ERRORS['filter_op'] . ': ' . $condition['op']),
        };
    }

    /**
     * A record as the admin panel sees it: system columns under their own
     * names, everything else under the logical names of the schema.
     */
    protected function present(FeedItem $item, Feed $feed): array
    {
        $fields = [];

        foreach ($this->schemaFields($feed) as $code => $field) {
            $value = $item->$code;

            $fields[$code] = $value instanceof \DateTimeInterface
                ? $value->format('Y-m-d H:i:s')
                : $value;
        }

        return [
            'id'         => (int) $item->id,
            'feedId'     => (int) $item->feed_id,
            'position'   => (int) $item->__position,
            'visible'    => (bool) $item->__visible,
            'created_at' => (string) $item->created_at,
            'updated_at' => (string) $item->updated_at,
            'fields'     => $fields,
        ];
    }

    /**
     * All fields of a schema in one list, code => description. The __data
     * container is skipped and its nested fields take its place: for a record
     * they are ordinary fields, they simply live in the json.
     */
    protected function schemaFields(Feed $feed): array
    {
        $fields = [];

        foreach ($feed->schema['fields'] ?? [] as $field) {
            $code = (string) ($field['code'] ?? '');

            if ($code === '') {
                continue;
            }

            if (($field['column'] ?? null) === '__data') {
                foreach ($field['data'] ?? [] as $nested) {
                    if (isset($nested['code'])) {
                        $fields[(string) $nested['code']] = $nested;
                    }
                }

                continue;
            }

            $fields[$code] = $field;
        }

        return $fields;
    }

    /**
     * Everything that can be wrong with the values of a record, collected per
     * field: the form has to highlight the input, not show one message.
     */
    protected function checkValues(Feed $feed, FeedItem $item, array $values, array $known): void
    {
        $rules = [];

        foreach ($known as $code => $field) {
            if (! empty($field['validation'])) {
                $rules[$code] = $field['validation'];
            }
        }

        // only the fields that came are validated
        $validator = Validator::make($values, array_intersect_key($rules, $values));

        $errors = $validator->fails() ? $validator->errors()->toArray() : [];

        foreach ($values as $code => $value) {
            $field = $known[$code];

            $empty = $value === null || $value === '';

            // uniqueness runs over the whole feed, hidden records included:
            // __visible means "do not show", not "free the value"
            if (! empty($field['unique']) && ! $empty) {
                $taken = $feed->items()
                    ->where($code, $value)
                    ->where('id', '!=', $item->id)
                    ->exists();

                if ($taken) {
                    $errors[$code][] = self::ERRORS['value_taken'] . ': ' . $value;
                }
            }

            if (isset($field['relation']) && ! $empty) {
                $error = $this->linkError($item, $field, (int) $value);

                if ($error !== null) {
                    $errors[$code][] = $error;
                }
            }
        }

        if ($errors !== []) {
            throw new FeedValidationException($errors);
        }
    }

    /** What is wrong with a link, or null when it is fine. */
    protected function linkError(FeedItem $item, array $field, int $targetId): ?string
    {
        $target = FeedItem::find($targetId);

        if (! $target) {
            return self::ERRORS['link_not_found'] . ': ' . $targetId;
        }

        if ((int) $target->feed_id !== (int) ($field['relation']['feed_id'] ?? 0)) {
            return self::ERRORS['link_wrong_feed'] . ': ' . $targetId;
        }

        $path = $this->linkPath($target, (int) $item->id);

        if ($path !== []) {
            return self::ERRORS['link_cycle'] . ': ' . implode(' → ', array_merge([(int) $item->id], $path));
        }

        return null;
    }

    /**
     * Path of links from a record to the one we are looking for, empty when
     * there is none. Used to catch a ring before it is written: two records
     * holding each other could never be deleted afterwards.
     */
    protected function linkPath(FeedItem $from, int $lookingFor, array $seen = []): array
    {
        if ((int) $from->id === $lookingFor) {
            return [$lookingFor];
        }

        if (isset($seen[$from->id])) {
            return [];
        }

        $seen[$from->id] = true;

        for ($i = 1; $i <= FeedItem::LINKS; $i++) {
            $nextId = $from->{'__link_' . $i};

            if (! $nextId) {
                continue;
            }

            $next = FeedItem::find($nextId);

            if (! $next) {
                continue;
            }

            $path = $this->linkPath($next, $lookingFor, $seen);

            if ($path !== []) {
                return array_merge([(int) $from->id], $path);
            }
        }

        return [];
    }

    /** Value of the first field of a record — enough to recognise it in a list. */
    protected function firstValue(FeedItem $item): mixed
    {
        $feed = $item->feed;

        if (! $feed) {
            return null;
        }

        $codes = array_keys($this->schemaFields($feed));

        return $codes === [] ? null : $item->{$codes[0]};
    }

    /** The record named by the id param, or a readable error. */
    protected function item(array $params): FeedItem
    {
        $id = (int) ($params['id'] ?? 0);

        if ($id <= 0) {
            throw new Exception(self::ERRORS['item_id_required']);
        }

        $item = FeedItem::find($id);

        if (! $item) {
            throw new Exception(self::ERRORS['item_not_found'] . ': ' . $id);
        }

        return $item;
    }

    // === Общее ===

    /** The feed named by id or by code, or a readable error. */
    protected function feed(array $params): Feed
    {
        $id   = (int) ($params['id'] ?? 0);
        $code = trim((string) ($params['code'] ?? ''));

        // feedSave passes a new code along with the id: the id decides
        $feed = $id > 0
            ? Feed::find($id)
            : ($code !== '' ? Feed::where('code', $code)->first() : null);

        if ($id <= 0 && $code === '') {
            throw new Exception(self::ERRORS['feed_id_required']);
        }

        if (! $feed) {
            throw new Exception(self::ERRORS['feed_not_found'] . ': ' . ($id > 0 ? $id : $code));
        }

        return $feed;
    }

    /** Checks a feed code and gives it back trimmed. */
    protected function code(string $code, int $feedId): string
    {
        $code = trim($code);

        if ($code === '') {
            throw new Exception(self::ERRORS['code_empty']);
        }

        if (! preg_match('/^[A-Za-z0-9_-]+$/', $code)) {
            throw new Exception(self::ERRORS['code_format'] . ': ' . $code);
        }

        $taken = Feed::where('code', $code)
            ->where('id', '!=', $feedId)
            ->exists();

        if ($taken) {
            throw new Exception(self::ERRORS['code_taken'] . ': ' . $code);
        }

        return $code;
    }

    /** Writes positions 0, 1, 2 … over the given ids, in that order. */
    protected function renumber(array $ids): void
    {
        foreach ($ids as $position => $id) {
            Feed::whereKey($id)->update(['position' => $position]);
        }
    }

    /** The group named by the id param, or a readable error. */
    protected function group(array $params): FeedGroup
    {
        $id = (int) ($params['id'] ?? 0);

        if ($id <= 0) {
            throw new Exception(self::ERRORS['group_id_required']);
        }

        $group = FeedGroup::find($id);

        if (! $group) {
            throw new Exception(self::ERRORS['group_not_found'] . ': ' . $id);
        }

        return $group;
    }
}
