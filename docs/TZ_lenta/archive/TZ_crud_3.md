# MagicPro: алиасы полей ленты через Eloquent

## Задача

В таблице `feed_items` физические поля называются:

```text
string_1
string_2
text_1
bigint_1
boolean_1
```

Но у каждой ленты эти поля имеют свои логические имена.

Например, для ленты с `id = 2`:

```text
name        → string_1
description → text_1
```

Нужно иметь возможность писать:

```php
$feed->items()
    ->where('name', 'вася')
    ->get();
```

А Eloquent должен выполнить запрос:

```php
FeedItem::query()
    ->where('feed_id', 2)
    ->where('string_1', 'вася')
    ->get();
```

## Что нужно изменить

Новая модель не нужна.

Нужно:

```text
Feed.php             — существующая модель
FeedItem.php         — существующая модель
FeedItemBuilder.php  — один новый класс
```

---

## Структура `feeds.schema`

В колонке `schema` таблицы `feeds` хранится описание полей:

```json
{
  "string_1": {
    "code": "name",
    "label": "Имя"
  },
  "text_1": {
    "code": "description",
    "label": "Описание"
  }
}
```

Модель `Feed` должна приводить `schema` к массиву.

---

## Модель `Feed`

```php
<?php

namespace MagicProDatabaseModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feed extends Model
{
    protected $casts = [
        'schema' => 'array',
    ];

    public function items(): HasMany
    {
        $relation = $this->hasMany(
            FeedItem::class,
            'feed_id'
        );

        $relation
            ->getQuery()
            ->useFeedSchema($this->schema ?? []);

        return $relation;
    }
}
```

Метод `items()` делает две вещи:

1. Добавляет условие:

```php
where('feed_id', $this->id)
```

2. Передаёт описание полей в `FeedItemBuilder`.

---

## Модель `FeedItem`

```php
<?php

namespace MagicProDatabaseModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class FeedItem extends Model
{
    protected $table = 'feed_items';

    public function newEloquentBuilder($query): Builder
    {
        return new FeedItemBuilder($query);
    }
}
```

Этот метод говорит Laravel:

```text
для FeedItem использовать не стандартный Builder,
а наш FeedItemBuilder
```

---

## Класс `FeedItemBuilder`

```php
<?php

namespace MagicProDatabaseModels;

use Illuminate\Database\Eloquent\Builder;

class FeedItemBuilder extends Builder
{
    /**
     * Соответствие логических имён физическим колонкам.
     *
     * Пример:
     *
     * name        => string_1
     * description => text_1
     */
    protected array $fieldAliases = [];

    /**
     * Загружает описание полей текущей ленты.
     */
    public function useFeedSchema(array $schema): static
    {
        foreach ($schema as $column => $field) {
            $code = $field['code'] ?? null;

            if ($code) {
                $this->fieldAliases[$code] = $column;
            }
        }

        return $this;
    }

    /**
     * Возвращает физическое имя колонки.
     *
     * name     → string_1
     * feed_id  → feed_id
     */
    protected function realColumn(string $column): string
    {
        return $this->fieldAliases[$column] ?? $column;
    }

    /**
     * Подмена имени поля в where().
     */
    public function where(
        $column,
        $operator = null,
        $value = null,
        $boolean = 'and'
    ): static {
        if (is_string($column)) {
            $column = $this->realColumn($column);
        }

        return parent::where(
            $column,
            $operator,
            $value,
            $boolean
        );
    }

    /**
     * Подмена имени поля в orderBy().
     */
    public function orderBy(
        $column,
        $direction = 'asc'
    ): static {
        if (is_string($column)) {
            $column = $this->realColumn($column);
        }

        return parent::orderBy(
            $column,
            $direction
        );
    }
}
```

---

## Использование

Получаем ленту:

```php
$feed = Feed::findOrFail(2);
```

Ищем записи:

```php
$items = $feed->items()
    ->where('name', 'вася')
    ->get();
```

Eloquent реально выполнит запрос примерно такого вида:

```sql
select *
from feed_items
where feed_id = 2
  and string_1 = 'вася';
```

Сортировка:

```php
$items = $feed->items()
    ->where('name', 'вася')
    ->orderBy('name')
    ->get();
```

Фактически:

```sql
select *
from feed_items
where feed_id = 2
  and string_1 = 'вася'
order by string_1 asc;
```

---

## Что уже будет работать

```php
$feed->items()
    ->where('name', 'вася')
    ->get();
```

```php
$feed->items()
    ->where('description', 'like', '%текст%')
    ->get();
```

```php
$feed->items()
    ->orderBy('name')
    ->get();
```

```php
$feed->items()
    ->where('feed_id', 2)
    ->get();
```

Обычные физические поля тоже продолжают работать.

---

## Что пока не будет автоматически переводиться

В первой версии алиасы будут работать только в методах, которые мы переопределили:

```text
where
orderBy
```

Позже можно добавить:

```text
orWhere
whereIn
whereNotIn
whereBetween
select
pluck
value
update
groupBy
```

Но не нужно добавлять всё сразу.

Добавляем методы только тогда, когда они реально понадобятся.

---

## Важное ограничение

Такой запрос работает:

```php
$feed->items()
    ->where('name', 'вася')
    ->get();
```

А такой запрос использовать нельзя:

```php
FeedItem::where('name', 'вася')->get();
```

Во втором случае неизвестно, какая используется лента и что именно означает поле `name`.

Правильно всегда начинать запрос через конкретную ленту:

```php
$feed->items()
```

или через отдельный метод:

```php
FeedItem::forFeed(2)
```

если такой метод будет добавлен позже.

---

## Итог

Новая модель не нужна.

Добавляется только один новый класс:

```text
FeedItemBuilder
```

И небольшие изменения в существующих моделях:

```text
Feed
FeedItem
```

Рабочий запрос выглядит просто:

```php
$items = Feed::findOrFail(2)
    ->items()
    ->where('name', 'вася')
    ->get();
```

При этом в базе остаются физические колонки:

```text
string_1
text_1
bigint_1
boolean_1
```

А в коде используются понятные имена:

```text
name
description
published_at
visible
```


---

## Замечания

**1. Формат `schema` не тот, что в `TZ_data_9.md`.** Здесь объект, ключ — колонка. В ТЗ —
массив `fields`, колонка лежит внутри поля. Оба варианта рабочие, но у массива порядок
элементов задаёт порядок полей в форме, а у объекта порядок ключей формально не
гарантирован. Нужно выбрать один формат, иначе `useFeedSchema` не разберёт реальную схему.

**2. Колонок `text_1` и `boolean_1` в модуле нет.** По ТЗ слоты такие: `string_1` …
`string_5`, `bigint_1` … `bigint_3`, `date_1` … `date_3`, `bool_1` … `bool_3`,
`link_1` … `link_3`. В примерах стоят несуществующие имена.

**3. `orderByDesc`, `latest`, `oldest` алиасы не переведут.** Мы переопределяем `orderBy` у
Eloquent-билдера, а эти методы живут в Query Builder и зовут его собственный `orderBy` —
наш код в обход. То же с `whereBetween`, `whereIn` и прочими: они и так в списке «позже»,
но `orderByDesc` выглядит как уже работающий, а это не так.

**4. `where` с массивом и с замыканием не переводится.** `where(['name' => 'вася'])` —
`$column` не строка, проверка `is_string` его пропустит. `where(fn($q) => $q->where('name',
…))` — внутрь замыкания приходит новый билдер, у которого `fieldAliases` пустой. Оба случая
дадут запрос по несуществующей колонке.

**5. Схема разбирается на каждый вызов `items()`.** Мелочь, но если по ленте идёт десяток
запросов на страницу, разбор повторяется десять раз. Достаточно запомнить результат в
модели `Feed`.

**6. Вход в запрос описан не так, как в ТЗ.** Здесь `Feed::findOrFail(2)->items()`, в
`TZ_data_9.md` — `MproHelper::getFeed('news')`, по коду ленты. В блейдах удобнее код: он
стабильнее числового `id` и переживает перенос базы. Стоит договориться об одном входе.

**7. Чтение полей записи не покрыто.** После выборки запись отдаёт физические имена:
`$item->string_1`, а не `$item->name`. Для блейдов это главное, ради чего всё затевалось,
так что стоит хотя бы отметить, что это следующий шаг.

**8. Поля из `data` в алиасы не попадают** — и не должны, поиск по ним запрещён. Побочный
эффект: `where('body', …)` уйдёт в базу как есть и даст ошибку SQL про неизвестную колонку.
Ошибка правильная по сути, но невнятная. Если это мешает — лечится позже одной проверкой,
сейчас трогать не нужно.
