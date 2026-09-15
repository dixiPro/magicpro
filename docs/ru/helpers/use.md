<!-- Собрано командой `php artisan magicpro:docs` из phpdoc класса MproHelper.
     Руками не править: правка потеряется при следующей сборке. -->

# Хелперы: как пользоваться

Набор функций, доступных отовсюду: из блейда статьи, из её контроллера, из
компонента и из ленты.

Все методы статические, класс лежит в глобальном пространстве имён и
подключается сам. В блейдах и контроллерах статей пишется сразу, без `use`:

    MproHelper::getArtById(139);

**Дерево статей.** Два семейства, и путать их дорого. `getArtById`,
`getArtByName` и `getParent` отдают статью целиком, со всеми полями.
`getChildrenById` и `getChildrenByName` отдают короткие записи для меню —
`id`, `title`, `name`, `menuOn`, `updated_at`, а по id ещё и `npp`, — и
отсеивают всё, у чего `menuOn = false`.

    <ul>
    @foreach (MproHelper::getChildrenByName('topMenu') as $child)
        <li><a href="/{{ $child['name'] }}">{{ $child['title'] }}</a></li>
    @endforeach
    </ul>

    @php( $parent = MproHelper::getParent($Env['artId']) )

    @if ($parent)
        <a href="/{{ $parent['name'] }}">Вверх: {{ $parent['title'] }}</a>
    @endif

Ничего не найдено — приходит пустой массив, а не ошибка: и у несуществующего
id, и у корня, у которого родителя нет.

**Какой метод под какую задачу**

| Задача | Метод |
| --- | --- |
| статья, её родитель, дети, путь до корня | `getArtById`, `getArtByName`, `getParent`, `getChildrenById`, `getChildrenByName`, `getPathToRootById` |
| показать страницу документации пакета | `getDoc` |
| отправить письмо прямо сейчас | `sendMail` |
| написать в свой дневной лог | `addLog` |
| сообщение в телеграм | `telegramSend` |
| reCAPTCHA: ключ и проверка | `getRecaptureKey`, `verifyRecapture` |
| зашифровать и расшифровать массив | `crypt`, `decrypt` |
| уменьшить картинку, почистить её кеш | `imageReduceX`, `imageReduceY`, `imageCacheClear`, `imageCacheCleanup` |
| MIME по расширению | `imageType` |
| текст записи ленты с подстановками | `feedText` |
| markdown оператора в html | `mdToHtml` |
| строка в кусок адреса, текст под мета-теги | `translitForUrl`, `trimAndCutText` |
| посмотреть значение при отладке | `dump` |

## Методы

[getDoc](#getdoc) · [getRecaptureKey](#getrecapturekey) · [verifyRecapture](#verifyrecapture) · [sendMail](#sendmail) · [addLog](#addlog) · [telegramSend](#telegramsend) · [crypt](#crypt) · [decrypt](#decrypt) · [dump](#dump) · [imageReduceX](#imagereducex) · [imageReduceY](#imagereducey) · [imageCacheClear](#imagecacheclear) · [imageCacheCleanup](#imagecachecleanup) · [imageType](#imagetype) · [feedText](#feedtext) · [mdToHtml](#mdtohtml) · [translitForUrl](#translitforurl) · [trimAndCutText](#trimandcuttext) · [getParent](#getparent) · [getChildrenById](#getchildrenbyid) · [getChildrenByName](#getchildrenbyname) · [getArtByName](#getartbyname) · [getArtById](#getartbyid) · [getPathToRootById](#getpathtorootbyid)

### getDoc

```php
MproHelper::getDoc(string $name, string $lang = '', bool $renderHtml = true): string
```

Страница документации пакета в html, источник — markdown в `docs/<язык>/`.

`$name` — имя файла без `.md`, можно с папкой: `mainUse/use`.
`$lang` — пустой берётся из настроек; нет перевода — покажется ru.
`$renderHtml` — `false` отдаёт markdown как есть.

    {!! MproHelper::getDoc('feed/use') !!}

### getRecaptureKey

```php
MproHelper::getRecaptureKey(): string
```

Публичный site key reCAPTCHA из `RECAPTCHA_SITE_KEY`. Тот, что виден в
исходнике страницы; секретный ключ сюда не приходит.

### verifyRecapture

```php
MproHelper::verifyRecapture(string $response): bool
```

Проверяет токен reCAPTCHA у Google. `true` только при успехе, любая
ошибка сети даёт `false`.

`$response` — токен из формы. Секретный ключ берётся из
`RECAPTCHA_SECRET_KEY` внутри и не передаётся.

### sendMail

```php
MproHelper::sendMail(array $params): array
```

Отправляет письмо сразу. Возвращает `status`, `errorMsg`, `data`; ошибка
не бросается, а приходит в ответе, и всё пишется в лог `mail`.

`$params`: `email`, `subj`, `html`, необязательные `replyTo` и `fromName`.

| Ключ       | Обяз. | Что это                                          |
| ---------- | ----- | ------------------------------------------------ |
| `email`    | да    | получатель; заблокированный адрес не пройдёт     |
| `subj`     | да    | тема, не короче 8 символов; короче — отказ        |
| `html`     | да    | тело письма, не короче 16 символов               |
| `replyTo`  | нет   | куда уйдёт ответ                                 |
| `fromName` | нет   | имя отправителя перед адресом                    |

    MproHelper::sendMail(['email' => $to, 'subj' => 'Заказ принят', 'html' => $html]);

`replyTo` нужен потому, что письмо уходит с `MAIL_FROM_ADDRESS`, а его
никто не читает: заявка с формы приходит менеджеру, и «Ответить» должно
вести клиенту, а не роботу. Непустой `replyTo` проверяется как адрес,
кривой уронит отправку в `errorMsg`.

`fromName` — только подпись перед адресом: `Магазин <info@site.ru>`. Сам
адрес не меняется, он подтверждён в SES. Пусто — берётся `MAIL_FROM_NAME`
из настроек проекта.

У обоих необязательных ключей отсутствие и пустая строка — одно и то же.
Лишние ключи молча игнорируются: опечатка выглядит как «параметр не
сработал», а не как ошибка.

Отложенная отправка и очередь — не сюда, это `docs/ru/mail/`.

### addLog

```php
MproHelper::addLog(string $logName, array|string $data): void
```

Пишет строку в свой лог. Файл на день и хранится две недели, поэтому на
диске он датирован: `storage/logs/<имя>-ГГГГ-ММ-ДД.log`, а `<имя>.log` —
только имя, которое даётся ротации.

`$logName` — имя лога, оно же имя файла. `$data` — строка или массив;
массив разворачивается в строки `ключ: значение`, а вложенный массив —
в JSON.

    MproHelper::addLog('order', ['id' => $id, 'sum' => $sum]);

Исключений не бросает. Не записался лог — права на файл, место на диске —
строка уходит в системный лог PHP (`error_log`), а вызывающий работает
дальше: задача крона, письмо и страница из-за лога не падают.

### telegramSend

```php
MproHelper::telegramSend(string $message, string $chat_id, string $botToken, string $mode = 'HTML'): array
```

Шлёт сообщение в телеграм. Возвращает ответ телеграма как есть, запись
уходит в лог `telegram`.

`$message` — текст, `$chat_id` — чат, `$botToken` — токен бота,
`$mode` — разметка текста: `HTML` или `Markdown`.

### crypt

```php
MproHelper::crypt(array $data, string $key): string
```

Шифрует массив в строку AES-256-CBC со случайным IV. IV кладётся в начало.
Обратно — `decrypt` с тем же ключом.

Строка обычная base64, в ней бывают `+`, `/` и `=`. В адрес её класть
через `urlencode()`, иначе часть символов потеряется по дороге.

Подписи нет: шифр скрывает содержимое, но не доказывает, что его не
подменили. Для ссылки подтверждения этого хватает — подобрать ключ, чтобы
получился осмысленный json, нельзя, — а вот доверять расшифрованному как
подписанному не стоит.

`$data` — массив, `$key` — ключ шифрования.

Удобно для токенов в ссылках подтверждения: положил в ссылку, получил
обратно массив.

    $token = MproHelper::crypt(['id' => 42, 'email' => $email], $key);
    $data  = MproHelper::decrypt($token, $key);   // ['id' => 42, ...]

### decrypt

```php
MproHelper::decrypt(string $data, string $key): array
```

Разбирает строку от `crypt` обратно в массив. Чужая строка или другой
ключ дают пустой массив, исключения нет.

`$data` — строка, `$key` — тот же ключ.

### dump

```php
MproHelper::dump($var, bool $showXmp = true): void
```

Печатает что угодно на страницу json-ом, для отладки. Ничего не
возвращает.

`$var` — что показать, `$showXmp` — обернуть в `<xmp>`; `false` печатает
голый json.

    {{ MproHelper::dump($item->fields()) }}

### imageReduceX

```php
MproHelper::imageReduceX(string $file, int $width, ?string $format = null, ?int $quality = null): array
```

Ресайз по ширине, на лету и с кешем. Исходник не трогается.

`$file` — **абсолютный путь в файловой системе**. Путь из поля картинки
ленты — это url, его надо развернуть: `public_path($item->img1['path'])`.
Один и тот же файл, переданный по-разному, попадёт в разные ветки кеша.

`$width` — ширина в px, `$format` и `$quality` без значения берутся из
настроек. Шкала четвёртого аргумента зависит от формата: у webp, avif и
jpg это качество 10–100, у png — сжатие 0–9.

Возвращает `path`, `width`, `height`, `size`, `ms`, `rotated`,
`rotateMs`, `cmd`, `errorMsg`. При ошибке `path` пустой, причина в
`errorMsg`, заглушка не подставляется.

    $img = MproHelper::imageReduceX(public_path($item->img1['path']), 800);

Это вход в ресайзер, подробности — `docs/ru/image/use.md`.

### imageReduceY

```php
MproHelper::imageReduceY(string $file, int $height, ?string $format = null, ?int $quality = null): array
```

То же, что `imageReduceX`, только по высоте.

`$height` — высота в px, остальное как у ресайза по ширине.

### imageCacheClear

```php
MproHelper::imageCacheClear(string $file): int
```

Убирает из кеша все производные одного исходника, любого размера и
формата. Возвращает число удалённых файлов.

`$file` — абсолютный путь к исходнику, тот же, что даётся ресайзу. Сам
файл может быть уже удалён: для поиска производных нужен только путь.

### imageCacheCleanup

```php
MproHelper::imageCacheCleanup(): array
```

Чистит кеш от производных, у которых не стало исходника. Возвращает
`files`, `bytes`, `kept`, `skipped`. Зовётся из крона, через контроллер
статьи.

    return MproHelper::imageCacheCleanup();

### imageType

```php
MproHelper::imageType(string $text): string
```

Mime картинки по расширению имени: `image/jpeg`, `image/png`,
`image/webp`, `image/gif`. Прочее — пустая строка.

`$text` — имя файла или путь.

### feedText

```php
MproHelper::feedText(MagicProDatabaseModels\FeedItem $item, string $code): string
```

Текст записи ленты, готовый к выводу: разворачивает подстановки `#поле#`
и теги magic-компонентов внутри текста.

`$item` — запись ленты, `$code` — имя её текстового поля.

    {!! MproHelper::feedText($item, 'body') !!}

### mdToHtml

```php
MproHelper::mdToHtml(string $md): string
```

Markdown в html для текста, который пишет оператор: html внутри
вырезается, ссылки с javascript выбрасываются. Тем и отличается от
`getDoc`: тот читает файлы самого пакета и им доверяет.

`$md` — исходный markdown.

### translitForUrl

```php
MproHelper::translitForUrl(string $text, int $limit = 200): string
```

Строка в кусок адреса: латиница в нижнем регистре, цифры и дефис. Что не
переводится — выбрасывается.

`$text` — исходная строка, `$limit` — потолок длины, режется по границе
слова; `0` — не резать. Одно слово длиннее лимита режется по лимиту:
пустой адрес хуже обрубка.

    $slug = MproHelper::translitForUrl($item->title);

Таблица закрытая: русский и сербский переводятся, пробелы, дефисы и
подчёркивания сводятся к одному дефису, а всё прочее — знаки препинания,
кавычки, иероглифы, эмодзи — просто выбрасывается. Из «Как выбрать
ноутбук?» получится `kak-vybrat-noutbuk`, а из строки без единой знакомой
буквы — пустая.

Этим считается `__slug` записи ленты. К именам статей отношения не имеет:
там свои правила и свой транслит в браузере.

### trimAndCutText

```php
MproHelper::trimAndCutText(string $text, int $limit = 0): string
```

Чистый текст из размеченного: снимает теги и entity, выбрасывает эмодзи,
сводит пробелы и переносы к одному пробелу. Для description и анонсов.

`$text` — исходный текст, `$limit` — потолок длины, режется по границе
слова; `0` — не резать.

    $descr = MproHelper::trimAndCutText($item->body, 160);

### getParent

```php
MproHelper::getParent(int $id): array
```

Родительская статья. Пустой массив у корня и у несуществующего id.

`$id` — id статьи.

### getChildrenById

```php
MproHelper::getChildrenById(int $artId): array
```

Дети статьи по её id, в порядке `npp`. Только те, у кого стоит `menuOn`:
это меню. Поля `id`, `title`, `name`, `menuOn`, `updated_at`, `npp`.

`$artId` — id родителя.

### getChildrenByName

```php
MproHelper::getChildrenByName(string $name): array
```

То же меню, но родитель ищется по имени статьи. Обычный вход из блейда.

`$name` — имя статьи-родителя.

    $menu = MproHelper::getChildrenByName('topMenu');

### getArtByName

```php
MproHelper::getArtByName(string $name): array
```

Статья по имени, все её поля. Нет такой — пустой массив.

`$name` — имя статьи, оно же её адрес.

### getArtById

```php
MproHelper::getArtById(int $id): array
```

Статья по id, все её поля. Нет такой — пустой массив.

`$id` — id статьи.

### getPathToRootById

```php
MproHelper::getPathToRootById(int $id): array
```

Путь от корня до статьи — массив имён по порядку, для хлебных крошек.
Оборванная цепочка отдаёт то, что успело собраться. Кольцо в `parentId`
тоже: обход останавливается, второй раз в ту же статью не заходит.

`$id` — id статьи.
