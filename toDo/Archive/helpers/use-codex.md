# MproHelper: как пользоваться

Это ручная, проверенная по реализации версия справочника. Основной
`docs/ru/helpers/use.md` собирается из PHPDoc класса `MproHelper` командой
`php artisan magicpro:docs`; его нельзя править вручную. Этот файл генератор не
перезаписывает.

`MproHelper` находится в глобальном пространстве имён, подключается пакетом и
содержит только статические методы. В Blade и PHP его вызывают без `use`:

```php
$article = MproHelper::getArtById(139);
```

## Быстрый выбор метода

| Задача | Метод |
| --- | --- |
| показать Markdown-файл документации | `getDoc` |
| получить site key / проверить reCAPTCHA | `getRecaptureKey`, `verifyRecapture` |
| сразу отправить письмо | `sendMail` |
| записать отдельный дневной лог | `addLog` |
| отправить сообщение в Telegram | `telegramSend` |
| зашифровать / расшифровать массив | `crypt`, `decrypt` |
| вывести значение при отладке | `dump` |
| уменьшить изображение | `imageReduceX`, `imageReduceY` |
| очистить кеш изображений | `imageCacheClear`, `imageCacheCleanup` |
| определить MIME по расширению | `imageType` |
| развернуть вставки текста ленты | `feedText` |
| безопаснее преобразовать Markdown оператора | `mdToHtml` |
| сделать slug | `translitForUrl` |
| получить короткий чистый текст | `trimAndCutText` |
| прочитать дерево статей | `getParent`, `getChildrenById`, `getChildrenByName`, `getArtByName`, `getArtById`, `getPathToRootById` |

## Документация

### getDoc

```php
MproHelper::getDoc(
    string $name,
    string $lang = '',
    bool $renderHtml = true
): string
```

Читает Markdown из `docs/<язык>/<имя>.md`. В имени разрешены вложенные папки:

```blade
{!! MproHelper::getDoc('feed/use') !!}
```

- `$name` передаётся без `.md`;
- пустой `$lang` берётся из `MagicGlobals::$INI['LANGUAGE']`;
- отсутствующий перевод заменяется русским файлом;
- `$renderHtml = true` возвращает HTML и кеширует его;
- `false` возвращает исходный Markdown без рендера и без HTML-кеша;
- неверный путь или отсутствующий файл дают пустую строку и запись в лог `doc`.

Сегменты имени допускают латинские буквы, цифры, `_` и `-`. Точки и
`../` запрещены.

## reCAPTCHA

### getRecaptureKey

```php
MproHelper::getRecaptureKey(): string
```

Возвращает публичный `RECAPTCHA_SITE_KEY`. Его можно помещать в HTML. Секретный
ключ метод не возвращает.

### verifyRecapture

```php
MproHelper::verifyRecapture(string $response): bool
```

Проверяет пользовательский токен через `API_Auth` и Google:

```php
if (!MproHelper::verifyRecapture((string) request('g-recaptcha-response'))) {
    return back()->withErrors(['captcha' => 'Проверка не пройдена']);
}
```

`true` приходит только при подтверждении Google. Пустой токен, отсутствие
секрета, отрицательный ответ, HTTP-ошибка и сетевое исключение превращаются в
`false`.

## Почта

### sendMail

```php
MproHelper::sendMail(array $params): array
```

Немедленно отправляет письмо через команду `API_Mail::sendNow`.

```php
$result = MproHelper::sendMail([
    'email' => 'manager@example.com',
    'subj' => 'Новый заказ',
    'html' => '<p>Получен новый заказ №42</p>',
    'replyTo' => 'customer@example.com',
    'fromName' => 'Магазин',
]);

if (!$result['status']) {
    // Show a neutral message to the visitor; inspect the mail log internally.
}
```

| Ключ | Обязателен | Правило |
| --- | --- | --- |
| `email` | да | корректный и не заблокированный адрес |
| `subj` | да | строка не короче 8 символов |
| `html` | да | строка не короче 16 символов |
| `replyTo` | нет | пусто или корректный email для кнопки «Ответить» |
| `fromName` | нет | подпись перед системным адресом отправителя |

Ответ всегда предполагается в форме:

```php
[
    'status' => true,
    'errorMsg' => '',
    'data' => [
        'id' => 15,
        'mail_id' => '...',
        'provider_message_id' => '...',
        'status' => 'sent',
    ],
]
```

Хелпер не предназначен для очереди. Отложенная отправка и повторные попытки
описаны в `../mail/use.md`.

## Логи и Telegram

### addLog

```php
MproHelper::addLog(string $logName, string|array $data): void
```

Создаёт отдельный дневной лог уровня `Info`, хранит до 14 файлов:

```php
MproHelper::addLog('order', [
    'id' => $orderId,
    'sum' => $sum,
]);
```

Фактическое имя текущего файла:
`storage/logs/order-YYYY-MM-DD.log`. Массив превращается в строки
`key: value`; вложенные массивы и объекты кодируются в JSON.

`$logName` передавайте только константой приложения. Не подставляйте в него
пользовательский ввод: значение напрямую участвует в пути файла.

### telegramSend

```php
MproHelper::telegramSend(
    string $message,
    string $chat_id,
    string $botToken,
    string $mode = 'HTML'
): array
```

Делает POST в Telegram Bot API, записывает `status`, `chat_id` и текст в лог
`telegram`, возвращает декодированный JSON-ответ:

```php
try {
    $response = MproHelper::telegramSend(
        '<b>Новый заказ</b>',
        $chatId,
        $botToken
    );
} catch (\Throwable $e) {
    // Network and logging exceptions are not converted into a result array.
}
```

`$mode` передаётся как `parse_mode`; обычно это `HTML` или `Markdown`.
Токен бота не записывается в лог, но его всё равно нельзя хранить в Blade.

## Шифрование

### crypt и decrypt

```php
MproHelper::crypt(array $data, string $key): string
MproHelper::decrypt(string $data, string $key): array
```

```php
$token = MproHelper::crypt(['id' => 42, 'email' => $email], $key);
$data = MproHelper::decrypt($token, $key);
```

Используется AES-256-CBC со случайным IV. IV находится внутри результата, ключ
хранит вызывающий код.

Важные ограничения текущей реализации:

- результат — обычный Base64, а не URL-safe Base64; перед включением в URL его
  нужно кодировать как query parameter;
- CBC-токен не содержит MAC/подписи и не гарантирует целостность;
- повреждённые короткие строки могут вызвать PHP warning, который Laravel
  способен превратить в исключение.

Для токена авторизации или подтверждения, которому нельзя позволить незаметное
изменение, предпочтительнее штатный `Crypt` Laravel или подписанный URL.

## Отладка

### dump

```php
MproHelper::dump($var, bool $showXmp = true): void
```

Печатает JSON непосредственно в ответ:

```blade
@php(MproHelper::dump($item->fields()))
```

При `$showXmp = true` JSON оборачивается в `<xmp>`, иначе выводится без
обёртки. Метод ничего не возвращает.

Используйте его только при локальной отладке и только для JSON-совместимых
значений. Ресурсы, битая UTF-8 и другие неподдерживаемые значения могут дать
пустой вывод. Не передавайте непроверенные строки: вывод не является безопасным
HTML-компонентом.

## Изображения

### imageReduceX и imageReduceY

```php
MproHelper::imageReduceX(
    string $file,
    int $width,
    ?string $format = null,
    ?int $quality = null
): array

MproHelper::imageReduceY(
    string $file,
    int $height,
    ?string $format = null,
    ?int $quality = null
): array
```

`$file` — абсолютный путь файловой системы, не URL:

```php
$source = public_path($item->image['path']);
$image = MproHelper::imageReduceX($source, 800);

if ($image['errorMsg'] === '') {
    $src = $image['path'];
}
```

Ответ содержит `path`, `width`, `height`, `size`, `ms`, `rotated`,
`rotateMs`, `cmd`, `errorMsg`. При обычной ошибке `path` пустой.

`format` и `quality` без значения берутся из настроек. Для WebP, AVIF и JPEG
качество обычно находится в диапазоне 10–100, для PNG используется уровень
сжатия 0–9.

### imageCacheClear

```php
MproHelper::imageCacheClear(string $file): int
```

Удаляет все производные одного абсолютного пути и возвращает число удалённых
файлов. Исходник может уже отсутствовать.

### imageCacheCleanup

```php
MproHelper::imageCacheCleanup(): array
```

Удаляет производные, для которых не найден исходник:

```php
[
    'files' => 3,
    'bytes' => 248000,
    'kept' => 120,
    'skipped' => 2,
]
```

Полное описание кеша и форматов — в `../image/use.md`.

### imageType

```php
MproHelper::imageType(string $text): string
```

Определяет MIME только по расширению строки:

| Расширение | Результат |
| --- | --- |
| `jpg`, `jpeg` | `image/jpeg` |
| `png` | `image/png` |
| `webp` | `image/webp` |
| `gif` | `image/gif` |
| остальные | пустая строка |

Содержимое файла не проверяется.

## Тексты лент и Markdown

### feedText

```php
MproHelper::feedText(
    MagicProDatabaseModels\FeedItem $item,
    string $code
): string
```

Разворачивает `#field#`, `#image.alt#` и одиночные
`<x-magic::component />` в текстовом поле записи:

```blade
{!! MproHelper::feedText($item, 'body') !!}
```

Это вход в `FeedText::render()`; детали — в `../feed/use.md`.

### mdToHtml

```php
MproHelper::mdToHtml(string $md): string
```

Преобразует Markdown оператора в HTML с настройками
`html_input = strip` и `allow_unsafe_links = false`:

```blade
{!! MproHelper::mdToHtml($item->description) !!}
```

Пустая строка остаётся пустой. Этот метод рассчитан на ввод оператора.
`getDoc()` имеет другую модель доверия и при HTML-рендере документации не
включает эти ограничения явно.

## Строки

### translitForUrl

```php
MproHelper::translitForUrl(string $text, int $limit = 200): string
```

Оставляет латиницу в нижнем регистре, цифры и дефис. Русские и сербские буквы
транслитерируются; пробелы, `_` и разные дефисы сводятся к одному `-`;
остальные символы удаляются.

```php
$slug = MproHelper::translitForUrl('Как выбрать ноутбук?', 200);
// kak-vybrat-noutbuk
```

`$limit = 0` отключает ограничение. При превышении лимита метод пытается
отбросить неполное последнее слово по последнему дефису. Если дефиса нет,
длинное слово обрезается ровно по количеству символов.

### trimAndCutText

```php
MproHelper::trimAndCutText(string $text, int $limit = 0): string
```

Декодирует HTML entities, удаляет теги, часть символов emoji, сводит все
пробельные последовательности к одному пробелу:

```php
$description = MproHelper::trimAndCutText($item->body, 160);
```

`0` отключает обрезание. При положительном лимите метод старается убрать
последнее неполное слово. Для строки без пробелов текущая реализация может
вернуть `limit + 1` символ; если нужен строгий максимум, обрежьте результат
дополнительно.

## Дерево статей

Методы возвращают массивы, а не модели.

### getArtById и getArtByName

```php
MproHelper::getArtById(int $id): array
MproHelper::getArtByName(string $name): array
```

Возвращают все поля первой найденной статьи или `[]`.

### getParent

```php
MproHelper::getParent(int $id): array
```

Возвращает полную родительскую статью. Для корня, неизвестного id или
`parentId = 0` возвращает `[]`.

### getChildrenById

```php
MproHelper::getChildrenById(int $artId): array
```

Возвращает видимых в меню прямых детей, отсортированных по `npp`. Поля:
`id`, `title`, `name`, `menuOn`, `updated_at`, `npp`.

### getChildrenByName

```php
MproHelper::getChildrenByName(string $name): array
```

Сначала находит id родителя, затем возвращает его прямых детей с
`menuOn = true` по `npp`. Поля те же, но без `npp` в результате.

```blade
@foreach (MproHelper::getChildrenByName('topMenu') as $child)
    <a href="/{{ $child['name'] }}">{{ $child['title'] }}</a>
@endforeach
```

### getPathToRootById

```php
MproHelper::getPathToRootById(int $id): array
```

Возвращает имена от корня до указанной статьи:

```php
['root', 'catalog', 'cheese']
```

Каждый уровень читается отдельным запросом. Оборванная цепочка возвращает уже
собранную часть. Текущий цикл обрабатывает максимум 99 статей и не распознаёт
кольцо родителей отдельно.

## Общие предосторожности

- Не правьте generated `helpers/use.md` вручную.
- Не используйте helpers как место новой бизнес-логики: многие методы лишь
  делегируют в профильный модуль.
- Не рассчитывайте, что каждый метод перехватывает исключения.
- Не передавайте пользовательские значения как `logName`, ключ шифрования,
  bot token, путь документа или имя поля ленты без своей проверки.
- В публичной странице экранируйте данные; `dump`, `feedText` и готовый HTML
  требуют осознанного вывода.
