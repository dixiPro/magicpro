# MproHelper: полное внутреннее устройство

Документ для разработчика, который меняет `MproHelper`, генерацию его
справочника или один из модулей-делегатов. Пользовательские вызовы собраны в
`use.md`, который собирается из PHPDoc.

## Главный принцип

`MproHelper` — глобальный статический вход из Blade и контроллеров статей. Это
не фасад Laravel и не самостоятельный слой бизнес-логики. Часть методов
реализована прямо в классе, часть переводит короткий вызов в API или сервис
MagicPro.

При изменениях нужно различать три контракта:

1. Сигнатура и фактическое поведение метода.
2. PHPDoc `@ru/@en`, являющийся источником generated-справочника.
3. Ручная архитектурная документация `inside.md`.

Изменение только одного слоя создаёт расхождение.

## Подключение и область имён

`src/Helpers/MproHelper.php` объявляет класс без namespace. В
`MagicServiceProvider::boot()` выполняется:

```php
require_once __DIR__ . '/Helpers/MproHelper.php';
require_once __DIR__ . '/Helpers/TreeHelper.php';
require_once __DIR__ . '/Helpers/DumpHelper.php';
```

Поэтому `MproHelper::` доступен из любого namespace без импорта. PSR-4 этот
класс не загружает, второй класс с тем же глобальным именем объявить нельзя.

`TreeHelper` и `DumpHelper` подключаются как совместимость. Они не участвуют
в генерации `helpers/use.md`. В `TreeHelper` остаются копии нескольких старых
методов, причём `getParent()` там является заглушкой. Новое поведение следует
добавлять в профильный модуль и `MproHelper`, а не синхронизировать копии без
отдельного решения о совместимости.

## Как формируется use.md

```text
MproHelper PHPDoc
        ↓ Reflection
MagicProSrc\Docs\PhpDoc
        ↓ php artisan magicpro:docs
docs/ru/helpers/use.md
        ↓ docs/ru/index.json
DocsTree → /a_dmin/documentation
         → MCP list-docs/get-doc
```

`use.md` — артефакт сборки, а не место редактирования. В текущем
`DocsCommand::PAGES` есть одна страница:

```php
[
    'class' => MproHelper::class,
    'file' => 'ru/helpers/use.md',
    'title' => 'Хелперы: как пользоваться',
]
```

Команда `magicpro:docs`:

1. Просит `PhpDoc` вернуть class block и публичные методы на языке пути.
2. Строит заголовок с предупреждением о генерации.
3. Добавляет оглавление по именам методов.
4. Для каждого метода пишет сигнатуру из Reflection и Markdown выбранного
   языкового блока.
5. Полностью перезаписывает целевой файл через `File::put()`.

Из-за полной перезаписи ручная правка `use.md` исчезнет. Generated-файл может
отставать от исходника до запуска команды, поэтому в релизной проверке нужны и
PHPDoc, и пересборка.

### Разбор PHPDoc

`MagicProSrc\Docs\PhpDoc` читает ReflectionClass:

- берёт только public-методы, объявленные самим классом;
- сохраняет порядок, полученный от Reflection;
- метод без docblock всё равно попадает в список с пометкой об отсутствии
  описания;
- сигнатура строится из reflection types, nullable/union types, имён
  параметров, defaults и return type;
- массив default печатается как `[]`, строки — в одинарных кавычках.

Языковые блоки начинаются отдельной строкой `@ru` или `@en`. Текст до первого
языкового тега и блок без тегов считаются английскими. При отсутствии нужного
языка выбирается `en`, затем первый имеющийся блок.

Парсер хранит Markdown, а HTML делает через `Str::markdown()` только по запросу.
`DocsCommand` просит сырой Markdown.

### Кеш парсера

Разобранный класс кешируется в:

```text
storage/app/private/magic/phpdoc/2/MproHelper.json
```

Кеш используется, если его `filemtime` не старше PHP-файла. Ошибка создания
каталога или записи проглатывается: результат текущего Reflection всё равно
возвращается. Имя кеша основано только на short class name; при появлении двух
документируемых классов с одинаковым коротким именем будет коллизия.

### Как generated-файл показывается

`docs/ru/index.json` включает только `helpers/use.md` с `agent: true`.
`DocsTree` берёт из индекса порядок, название и разрешение для MCP:

- админка читает Markdown-файл через `DocsTree::page()` и рендерит его
  `MproHelper::mdToHtml()`;
- `list-docs` предлагает агенту только существующие страницы с `agent: true`;
- `get-doc` валидирует путь и вызывает `MproHelper::getDoc(..., false)`, то
  есть отдаёт сырой Markdown;
- orphan-файлы видны человеку в отдельном списке, но не выдаются MCP без строки
  индекса.

## Карта методов и владельцев логики

| Метод | Где находится основная логика |
| --- | --- |
| `getDoc` | `MproHelper`, Laravel Cache и Markdown |
| `getRecaptureKey` | `MproHelper` |
| `verifyRecapture` | `API_SiteAuth::verifyCaptcha` |
| `sendMail` | wrapper + `API_Mail::sendNow` |
| `addLog` | `MproHelper`, Monolog |
| `telegramSend` | `MproHelper`, Laravel HTTP client |
| `crypt`, `decrypt` | `MproHelper`, OpenSSL |
| `dump` | `MproHelper`, `json_encode` |
| image-методы | `ImageJob` |
| `feedText` | `FeedText` |
| `mdToHtml` | `MproHelper`, `Str::markdown` |
| строковые преобразования | `MproHelper` |
| дерево статей | `MproHelper`, модель `Article` |

## getDoc

`getDoc($name, $lang, $renderHtml)`:

1. Подставляет `MagicGlobals::$INI['LANGUAGE'] ?? 'ru'`, если язык пуст.
2. Проверяет имя регулярным выражением
   `^[A-Za-z0-9_-]+(/[A-Za-z0-9_-]+)*$`.
3. Проверяет язык через `^[A-Za-z-]+$`.
4. Строит путь
   `src/Helpers/../../docs/<lang>/<name>.md`.
5. Если файла нет и язык не `ru`, повторяет поиск в `ru`.
6. При неверном вводе или отсутствии файла вызывает `addLog('doc', ...)` и
   возвращает пустую строку.
7. При `renderHtml = false` читает файл напрямую.
8. Иначе использует `Cache::rememberForever`.

Ключ HTML-кеша:

```text
magicDoc:<lang>:<name>:<filemtime>
```

После изменения файла появляется новый ключ. Старые forever-записи сами не
удаляются. `filemtime` имеет ограниченную точность файловой системы: несколько
правок с одинаковой отметкой времени могут временно попасть в прежний ключ.

`getDoc` допускает вложенные сегменты и блокирует точки, поэтому путь вида
`feed/use` разрешён, а `../.env` собрать нельзя.

Рендер HTML документации использует обычный `Str::markdown($text)` без явных
ограничений `html_input`. Это намеренная модель доверия к файлам пакета.

## reCAPTCHA

`getRecaptureKey()` напрямую читает `env('RECAPTCHA_SITE_KEY')` и приводит к
строке.

`verifyRecapture($response)` вызывает `API_SiteAuth::verifyCaptcha($response)`.

Пустой `RECAPTCHA_SECRET_KEY` — `abort(500)`: исключение уходит наружу, ответ
сервера 500. Пустой токен — `false` без обращения к Google. Иначе form POST в
Google с `remoteip = request()->ip()`, connect timeout 2 секунды и общим timeout
4 секунды; ошибка сети или ответ без `success: true` — `false`.

Оба ключа читаются через `env()` во время выполнения, а не через config. После
`config:cache` они пустые: перед установкой нового ключа сбросить кеш
конфигурации (`php artisan config:clear`).

## sendMail

Wrapper переводит публичные имена параметров:

| Helper | API_Mail |
| --- | --- |
| `email` | `to` |
| `subj` | `subject` |
| `html` | `html` |
| `replyTo` | `replyTo` |
| `fromName` | `fromName` |

`API_Mail::run('sendNow')` возвращает envelope. При отрицательном статусе
wrapper создаёт исключение из `errorMsg`, сразу ловит его и сокращает ответ до
`status/errorMsg/data`. Лишние пользовательские ключи не передаются дальше.

`API_Mail` отвечает за:

- синтаксис и реестр адресов;
- блокировку получателя;
- минимум 8 символов темы и 16 символов HTML;
- проверку непустого `replyTo`;
- защиту от дублей;
- выбор SMTP или AWS API;
- запись сообщения в БД.

`fromName` пустой строкой откатывается к `config('mail.from.name')`; адрес
отправителя helper не принимает.

Логирование выполняется на двух уровнях:

- `AbstractMailApi` пишет внутреннее место любой ошибки в `mail`;
- `MproHelper::sendMail` пишет итог успешного или неуспешного вызова.

`addLog()` исключений не бросает, поэтому запись в лог внутри catch ответа не
ломает.

## addLog

Каждый вызов создаёт новый `Monolog\Logger` и новый
`RotatingFileHandler`:

```php
new RotatingFileHandler(
    storage_path("logs/{$logName}.log"),
    14,
    Level::Info
);
```

Monolog пишет фактический файл `<name>-YYYY-MM-DD.log` и хранит 14 дневных
файлов.

Метод исключений не бросает: запись идёт в `writeLog()`, обёрнутом в
try/catch. Не записалось — права на файл (крон и сайт работают от разных
пользователей), место на диске — строка `magicpro addLog(<имя>) failed: …`
уходит в `error_log()`, системный лог PHP, и вызывающий работает дальше.

Подготовка массива:

- вложенный array/object проходит `json_encode(..., JSON_UNESCAPED_UNICODE)`;
- bool становится `true` или `false`;
- null становится словом `null`;
- каждая пара превращается в `key: value`;
- итог обрезается через `trim()` и отправляется в `logger->info()`.

`logName` не проверяется и прямо интерполируется в путь. Внутренние вызовы
используют константные значения (`mail`, `doc`, `feed`, `cron`, `mcp`,
`telegram`). Публичный вызов с пользовательским именем может содержать
разделители пути, поэтому boundary должен обеспечивать вызывающий код.

Исключения создания/записи лога метод не ловит.

## telegramSend

Метод выполняет:

```php
Http::post(
    'https://api.telegram.org/bot' . $botToken . '/sendMessage',
    [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => $mode,
    ]
);
```

После ответа логирует `successful()`, chat id и полный message, затем возвращает
`$response->json()`. Это декодированное тело, а не объект Response.

HTTP timeout явно не задан. ConnectionException не перехватывается. Если Telegram вернул не JSON-массив, фактический результат
`json()` может конфликтовать с объявленным return type `array`.

## crypt и decrypt

`crypt()`:

1. Кодирует массив в JSON без escaped Unicode.
2. Получает 16 байт через `openssl_random_pseudo_bytes`.
3. Шифрует `AES-256-CBC` с options `0`; OpenSSL уже возвращает Base64
   ciphertext.
4. Соединяет бинарный IV и Base64 ciphertext.
5. Ещё раз кодирует всю строку обычным Base64.

`decrypt()` выполняет обратные операции: нестрогий `base64_decode`, первые
16 байт как IV, остаток в `openssl_decrypt`, затем `json_decode(..., true)`.

Следствия:

- результат содержит алфавит обычного Base64 (`+`, `/`, `=`) и требует URL
  encoding;
- ключ не нормализуется и не проверяется по длине;
- CBC не аутентифицирован: MAC/tag отсутствует, изменение ciphertext отдельно
  не обнаруживается;
- `base64_decode` не использует strict mode;
- длина decoded-строки не проверяется до OpenSSL;
- `json_decode` не проверяется через `json_last_error`;
- строка JSON не-array при успешной расшифровке нарушит return type;
- PHP warnings не перехватываются самим методом.

Для новых security-sensitive токенов предпочтительны Laravel `Crypt`,
`URL::signedRoute()` или AEAD с проверкой tag.

## dump

`dump($var, $showXmp)` вызывает `json_encode` с
`JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES` и сразу
делает `echo`.

При `showXmp = true` используется:

```html
<xmp style="line-height:1.2; font-size:12px;">...</xmp>
```

`json_encode` вызывается без `JSON_THROW_ON_ERROR`, поэтому resource или
битая UTF-8 возвращают `false`, а catch не выполняется. Метод печатает пустую
строку. Обёртка `xmp` не экранирует закрывающую последовательность
`</xmp>`; значение нельзя считать безопасным для недоверенного ввода.

`DumpHelper` — отдельный legacy-класс с `dump()` и `getJson()`; он не
используется generated-документацией.

## Изображения

`imageReduceX()` и `imageReduceY()` — однострочные вызовы:

```php
ImageJob::make($file, 'x', $width, $format, $quality);
ImageJob::make($file, 'y', $height, $format, $quality);
```

`imageCacheClear()` вызывает `ImageJob::clear($file)`,
`imageCacheCleanup()` — `ImageJob::cleanup()`.

Helper не нормализует путь, размер, формат и качество. Вся проверка, cache key,
lock, вызов encoder и форма результата принадлежат `ImageJob`. Документировать
новое image-поведение сначала нужно в `docs/ru/image/`, затем синхронизировать
PHPDoc короткого входа.

`imageType()` не читает файл. Он берёт
`strtolower(pathinfo($text, PATHINFO_EXTENSION))` и поддерживает только JPEG,
PNG, WebP и GIF.

## feedText и mdToHtml

`feedText(FeedItem $item, string $code)` полностью делегирует
`FeedText::render()`. Тот читает логическое поле записи, разворачивает метки и
рендерит только одиночные magic-component tags. Изменения синтаксиса находятся
в `src/Lenta/FeedText.php`.

`mdToHtml($md)`:

- возвращает `''` для пробельной строки;
- вызывает `Str::markdown()`;
- устанавливает `html_input => strip`;
- устанавливает `allow_unsafe_links => false`.

Это граница для Markdown оператора. Она отличается от HTML-ветки `getDoc`,
которая доверяет файлу пакета.

Админская документация через `DocsTree::page()` тоже проходит
`mdToHtml()`, тогда как прямой `getDoc(..., true)` использует обычный
`Str::markdown()`.

## translitForUrl

Закрытая таблица `TRANSLIT_URL` содержит:

- русскую кириллицу;
- сербскую кириллицу и латиницу;
- английский алфавит;
- цифры;
- пробел, underscore и несколько видов дефиса.

Если доступен `Normalizer`, вход сначала приводится к Unicode NFC. Затем
`preg_split('//u', ...)` разбивает строку на символы, каждый отсутствующий в
таблице отбрасывается. Повторные дефисы схлопываются, крайние удаляются.

Ограничение:

1. Если сразу за лимитом стоит дефис, срез пришёлся на границу слова —
   результат режется до `$limit` и больше не трогается.
2. Иначе режется до `$limit`, и если в обрезанном фрагменте есть дефис,
   удаляется всё после последнего дефиса вместе с ним.
3. Без дефиса остаётся жёсткий срез внутри длинного слова — намеренно: пустой
   адрес хуже обрубка.

`limit <= 0` фактически отключает обрезание, хотя PHPDoc называет только ноль.

## trimAndCutText

Порядок преобразований:

1. `html_entity_decode(..., ENT_QUOTES | ENT_HTML5, 'UTF-8')`.
2. `strip_tags()`.
3. Удаление Unicode-категорий `So` и `Cn`.
4. Замена `\s+` одним пробелом.
5. `trim()`.
6. При превышении лимита срез до `limit + 1`.
7. Удаление хвоста по `\s+\S*$`.

Это не полноценный Unicode emoji filter: modifiers, variation selectors,
zero-width joiners и keycap sequences относятся к другим категориям и могут
остаться. Для строки без пробела регулярное выражение последнего шага ничего не
удаляет, поэтому результат имеет `limit + 1` символ.

`strip_tags()` не вставляет пробел между соседними тегами: текст
`<p>A</p><p>B</p>` превращается в `AB`.

## Дерево статей

Все методы возвращают массивы и используют Eloquent-модель `Article` без
кеширования.

### getParent

Первый запрос читает только `parentId`. Пустой id родителя даёт `[]`, иначе
вызывается `getArtById`, то есть выполняется второй запрос за полной статьёй.

### getChildrenById

Один запрос:

```text
SELECT id, title, name, menuOn, updated_at, npp
WHERE parentId = ? AND menuOn = true
ORDER BY npp
```

### getChildrenByName

Сначала отдельным запросом читается id статьи по уникальному на уровне модели
`name`, затем дети:

```text
SELECT id, title, name, menuOn, updated_at
WHERE parentId = ? AND menuOn = true
ORDER BY npp
```

`npp` используется для SQL-порядка, но намеренно не выбран в результат.
Одинаковые `npp` не закрыты вторичной сортировкой.

Если имя не найдено, id равен null. Текущая миграция делает `parentId`
не-null с default 0, поэтому запрос детей обычно возвращает пустой массив.

### getArtByName и getArtById

Оба метода делают `select('*')->get()->toArray()` и возвращают первый элемент
или `[]`. `first()` не используется. Уникальность имени проверяется событием
модели, но в миграции на `name` обычный, не уникальный индекс.

### getPathToRootById

Цикл увеличивает счётчик перед проверкой `< 100`, поэтому обрабатывает максимум
99 моделей. Каждый шаг делает `Article::findOrFail()`, добавляет `name` в
начало массива и останавливается на `parentId == 0`.

Любой Throwable возвращает накопленный путь. Множество посещённых id не
хранится. Модель запрещает только `parentId == собственный id`, но не кольцо
между несколькими статьями, поэтому косвенный цикл повторяется до лимита и
возвращает длинный неполный массив.

Для меню в цикле страницы заранее получайте данные одним helper-вызовом. Не
вызывайте `getParent()` или `getArtById()` по одному разу на каждый элемент
большой коллекции.

## Границы исключений

| Метод | Обычная ошибка преобразуется | Что всё ещё может выйти |
| --- | --- | --- |
| `getDoc` | плохой путь / нет файла → `''` | ошибка Cache, чтения или Markdown |
| `verifyRecapture` | network Throwable → `false` | пустой `RECAPTCHA_SECRET_KEY` — HTTP 500 |
| `sendMail` | API error → отрицательный массив | нет |
| `telegramSend` | нет | HTTP |
| `crypt/decrypt` | OpenSSL false частично обработан | warnings, random/type errors |
| `dump` | Throwable в блоке → красная надпись | `json_encode(false)` не бросает и даёт пусто |
| image wrappers | обычные ошибки ImageJob → `errorMsg` | неперехваченные ошибки зависимостей |
| tree path | Throwable → накопленный путь | остальные tree queries бросают как Eloquent |

Не добавляйте в PHPDoc обещание «исключений нет», пока весь путь, включая
логирование ошибки, действительно не защищён.

## Как менять или добавлять helper

1. Убедиться, что короткий глобальный вход действительно нужен Blade или
   article controller.
2. Поместить бизнес-логику в профильный namespaced-класс.
3. Добавить максимально тонкий public static wrapper.
4. Определить точную сигнатуру, return shape и границу исключений.
5. Написать PHPDoc `@ru` и `@en` над методом; class block менять только для
   общей информации.
6. Сверить описание с телом wrapper и делегатом.
7. Проверить Reflection-представление nullable, union и defaults.
8. Запустить `php -l src/Helpers/MproHelper.php`.
9. Запустить `php artisan magicpro:docs` из корня Laravel-проекта: команда
   полностью обновит `docs/ru/helpers/use.md`.
10. Проверить diff generated-файла, оглавление, сигнатуры и оба языка PHPDoc.
11. Если изменилось профильное поведение, обновить документацию его модуля.

Не дописывайте строку метода вручную в `use.md`: оглавление и раздел создаются
автоматически для каждого public-метода.

## Матрица проверки

### Документация

- public-метод присутствует в generated-файле;
- signature совпадает с Reflection;
- `@ru` и `@en` описывают одинаковый контракт;
- generated header сохранён;
- ручных вставок в `use.md` нет;
- нужная страница присутствует в `index.json`, если она должна быть видна.

### Чистые методы

- `translitForUrl`: оба алфавита, combining Unicode, разделители, один длинный
  токен, отрицательный/нулевой/малый limit;
- `trimAndCutText`: entities, соседние теги, пробелы, emoji sequences, длинное
  слово и точная длина;
- `imageType`: регистр расширения, неизвестное расширение, URL query;
- `crypt/decrypt`: round-trip Unicode, неверный ключ, пустой/короткий Base64,
  изменённый ciphertext и не-array JSON;
- `dump`: array, object, invalid UTF-8, resource и строка с `</xmp>`.

### Интеграции

- reCAPTCHA: успех, отказ, пустой token/secret, timeout;
- mail: обязательные поля, blocked, duplicate, replyTo, оба транспорта, сбой
  логирования;
- Telegram: success/non-JSON/HTTP error/connection timeout;
- image: cache hit/miss, ошибка encoder, clear/cleanup;
- feed text: обычная метка, вложенный ключ, magic component и ошибочный tag;
- tree: root, неизвестный id/name, hidden child, одинаковые npp, оборванный
  parent, глубина больше 99 и косвенный цикл.

## Что не менять автоматически

- generated `docs/ru/helpers/use.md` вручную;
- `docs/ru/index.json` без отдельного задания;
- legacy `TreeHelper` и `DumpHelper` только ради косметического совпадения;
- профильные Mail/Image/Feed/Auth-классы при задаче только на helper;
- БД, Composer, git и опубликованные bundles без отдельного разрешения.
