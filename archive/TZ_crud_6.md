# MagicPro: Ленты CRUD

Источник истины

CRUD лент использует модели Laravel и Eloquent.

В таблице `feed_items` данные физически хранятся в универсальных колонках:

```text
string_1
string_2
bigint_1
date_1
bool_1
link_1
```

В коде приложения вместо физических названий колонок используются логические имена `code`, заданные в `feeds.schema`.

Например:

```text
name  → string_1
price → bigint_1
date  → date_1
```

## Пример поиска

Найти в ленте `Users` все записи, где поле `name` равно `Вася`:

```php
$feed = Feed::where('code', 'Users')->firstOrFail();

$items = $feed->items()
    ->where('name', 'Вася')
    ->get();
```

Перед выполнением запроса имя `name` автоматически заменяется на физическую колонку `string_1`.

Фактически Eloquent выполняет запрос:

```php
$items = FeedItem::query()
    ->where('feed_id', $feed->id)
    ->where('string_1', 'Вася')
    ->get();
```

## Пример поиска по нескольким полям

Найти товары ленты `Products`, у которых название равно `Мыло`, а цена больше `1000`:

```php
$feed = Feed::where('code', 'Products')->firstOrFail();

$items = $feed->items()
    ->where('name', 'Мыло')
    ->where('price', '>', 1000)
    ->get();
```

Если схема ленты содержит:

```text
name  → string_1
price → bigint_1
```

то запрос преобразуется в:

```php
$items = FeedItem::query()
    ->where('feed_id', $feed->id)
    ->where('string_1', 'Мыло')
    ->where('bigint_1', '>', 1000)
    ->get();
```

## Пример сортировки

Получить записи ленты `Users`, отсортированные по имени:

```php
$feed = Feed::where('code', 'Users')->firstOrFail();

$items = $feed->items()
    ->orderBy('name')
    ->get();
```

Фактически выполняется:

```php
$items = FeedItem::query()
    ->where('feed_id', $feed->id)
    ->orderBy('string_1')
    ->get();
```

## Подключение Builder

Модель `FeedItem` использует собственный Eloquent Builder:

```php
public function newEloquentBuilder($query)
{
    return new FeedItemBuilder($query);
}
```

Класс `FeedItemBuilder`:

- получает массив соответствий `code → физическая колонка`;
- заменяет логические имена полей в Eloquent-запросах;
- передаёт преобразованный запрос стандартному Eloquent Builder.

Пример массива соответствий:

```php
[
    'name'  => 'string_1',
    'price' => 'bigint_1',
]
```

Полный пример исходного запроса:

```php
$feed = Feed::where('code', 'Users')->firstOrFail();

$items = $feed->items()
    ->where('name', 'Вася')
    ->get();
```

После преобразования:

```php
$items = FeedItem::query()
    ->where('feed_id', $feed->id)
    ->where('string_1', 'Вася')
    ->get();
```

Источник массива соответствий не относится к механизму замены. Он может быть получен из `feeds.schema` и передан в `FeedItemBuilder` при создании запроса конкретной ленты.
