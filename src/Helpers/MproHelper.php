<?php

use MagicProDatabaseModels\Article;
use MagicProDatabaseModels\FeedItem;
use MagicProSrc\Lenta\FeedText;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use MagicProSrc\Api\API_Auth;
use MagicProSrc\Config\MagicGlobals;
use MagicProSrc\Image\ImageJob;
use MagicProSrc\Mail\API_Mail;
use Monolog\Logger;
use Monolog\Level;
use Monolog\Handler\RotatingFileHandler;


/**
 * @ru
 * Набор функций, доступных отовсюду: из блейда статьи, из её контроллера, из
 * компонента и из ленты.
 *
 * Все методы статические, класс лежит в глобальном пространстве имён и
 * подключается сам. В блейдах и контроллерах статей пишется сразу, без `use`:
 *
 *     MproHelper::getArtById(139);
 *
 * **Дерево статей.** Два семейства, и путать их дорого. `getArtById`,
 * `getArtByName` и `getParent` отдают статью целиком, со всеми полями.
 * `getChildrenById` и `getChildrenByName` отдают короткие записи для меню —
 * `id`, `title`, `name`, `menuOn`, `updated_at`, а по id ещё и `npp`, — и
 * отсеивают всё, у чего `menuOn = false`.
 *
 *     <ul>
 *     @foreach (MproHelper::getChildrenByName('topMenu') as $child)
 *         <li><a href="/{{ $child['name'] }}">{{ $child['title'] }}</a></li>
 *     @endforeach
 *     </ul>
 *
 *     @php( $parent = MproHelper::getParent($Env['artId']) )
 *
 *     @if ($parent)
 *         <a href="/{{ $parent['name'] }}">Вверх: {{ $parent['title'] }}</a>
 *     @endif
 *
 * Ничего не найдено — приходит пустой массив, а не ошибка: и у несуществующего
 * id, и у корня, у которого родителя нет.
 *
 * **Какой метод под какую задачу**
 *
 * | Задача | Метод |
 * | --- | --- |
 * | статья, её родитель, дети, путь до корня | `getArtById`, `getArtByName`, `getParent`, `getChildrenById`, `getChildrenByName`, `getPathToRootById` |
 * | показать страницу документации пакета | `getDoc` |
 * | отправить письмо прямо сейчас | `sendMail` |
 * | написать в свой дневной лог | `addLog` |
 * | сообщение в телеграм | `telegramSend` |
 * | reCAPTCHA: ключ и проверка | `getRecaptureKey`, `verifyRecapture` |
 * | зашифровать и расшифровать массив | `crypt`, `decrypt` |
 * | уменьшить картинку, почистить её кеш | `imageReduceX`, `imageReduceY`, `imageCacheClear`, `imageCacheCleanup` |
 * | MIME по расширению | `imageType` |
 * | текст записи ленты с подстановками | `feedText` |
 * | markdown оператора в html | `mdToHtml` |
 * | строка в кусок адреса, текст под мета-теги | `translitForUrl`, `trimAndCutText` |
 * | посмотреть значение при отладке | `dump` |
 *
 */
class MproHelper
{
    // language the documentation is written in, the one translations fall back to
    private const DOC_SOURCE_LANG = 'ru';

    /**
     * @ru
     * Страница документации пакета в html, источник — markdown в `docs/<язык>/`.
     *
     * `$name` — имя файла без `.md`, можно с папкой: `mainUse/use`.
     * `$lang` — пустой берётся из настроек; нет перевода — покажется ru.
     * `$renderHtml` — `false` отдаёт markdown как есть.
     *
     *     {!! MproHelper::getDoc('feed/use') !!}
     *
     * @en
     * A documentation page of the package as html, the source is markdown in
     * `docs/<lang>/`.
     *
     * `$name` — file name without `.md`, a folder is allowed: `mainUse/use`.
     * `$lang` — empty takes the one from the settings; no translation shows ru.
     * `$renderHtml` — `false` returns the markdown untouched.
     *
     *     {!! MproHelper::getDoc('feed/use') !!}
     */
    public static function getDoc(string $name, string $lang = '', bool $renderHtml = true): string
    {
        if ($lang === '') {
            $lang = (string) (MagicGlobals::$INI['LANGUAGE'] ?? 'ru');
        }

        // letters, digits, dash and underscore, a slash only between segments: a
        // dot cannot get in and ../ cannot be built, and both parts of the path
        // arrive as route parameters
        if (!preg_match('#^[A-Za-z0-9_-]+(/[A-Za-z0-9_-]+)*$#', $name) || !preg_match('/^[A-Za-z-]+$/', $lang)) {
            self::addLog('doc', ['error' => 'invalid name or lang', 'name' => $name, 'lang' => $lang]);
            return '';
        }

        $file = __DIR__ . '/../../docs/' . $lang . '/' . $name . '.md';

        // translations lag behind, so a missing one shows the source language
        // instead of an empty page
        if (!is_file($file) && $lang !== self::DOC_SOURCE_LANG) {
            $lang = self::DOC_SOURCE_LANG;
            $file = __DIR__ . '/../../docs/' . $lang . '/' . $name . '.md';
        }

        if (!is_file($file)) {
            self::addLog('doc', ['error' => 'file not found', 'file' => $file]);
            return '';
        }

        if (!$renderHtml) {
            return (string) file_get_contents($file);
        }

        // the markdown is converted once per file version: mtime is part of the key
        return Cache::rememberForever(
            'magicDoc:' . $lang . ':' . $name . ':' . filemtime($file),
            fn() => Str::markdown(file_get_contents($file))
        );
    }

    /**
     * @ru
     * Публичный site key reCAPTCHA из `RECAPTCHA_SITE_KEY`. Тот, что виден в
     * исходнике страницы; секретный ключ сюда не приходит.
     *
     * @en
     * The public site key of reCAPTCHA, from `RECAPTCHA_SITE_KEY`. The one
     * visible in the page source; the secret key never comes here.
     */
    public static function getRecaptureKey(): string
    {
        return (string) env('RECAPTCHA_SITE_KEY');
    }

    /**
     * @ru
     * Проверяет токен reCAPTCHA у Google. `true` только при успехе, любая
     * ошибка сети даёт `false`.
     *
     * `$response` — токен из формы. Секретный ключ берётся из
     * `RECAPTCHA_SECRET_KEY` внутри и не передаётся.
     *
     * @en
     * Checks a reCAPTCHA token with Google. `true` on success only, any network
     * trouble gives `false`.
     *
     * `$response` — the token from the form. The secret key is taken from
     * `RECAPTCHA_SECRET_KEY` inside and is never passed in.
     */
    public static function verifyRecapture(string $response): bool
    {
        return API_Auth::run('checkGoogleCapture', [
            'token' => $response,
        ])['status'];
    }

    /**
     * @ru
     * Отправляет письмо сразу. Возвращает `status`, `errorMsg`, `data`; ошибка
     * не бросается, а приходит в ответе, и всё пишется в лог `mail`.
     *
     * `$params`: `email`, `subj`, `html`, необязательные `replyTo` и `fromName`.
     *
     * | Ключ       | Обяз. | Что это                                          |
     * | ---------- | ----- | ------------------------------------------------ |
     * | `email`    | да    | получатель; заблокированный адрес не пройдёт     |
     * | `subj`     | да    | тема, не короче 8 символов; короче — отказ        |
     * | `html`     | да    | тело письма, не короче 16 символов               |
     * | `replyTo`  | нет   | куда уйдёт ответ                                 |
     * | `fromName` | нет   | имя отправителя перед адресом                    |
     *
     *     MproHelper::sendMail(['email' => $to, 'subj' => 'Заказ принят', 'html' => $html]);
     *
     * `replyTo` нужен потому, что письмо уходит с `MAIL_FROM_ADDRESS`, а его
     * никто не читает: заявка с формы приходит менеджеру, и «Ответить» должно
     * вести клиенту, а не роботу. Непустой `replyTo` проверяется как адрес,
     * кривой уронит отправку в `errorMsg`.
     *
     * `fromName` — только подпись перед адресом: `Магазин <info@site.ru>`. Сам
     * адрес не меняется, он подтверждён в SES. Пусто — берётся `MAIL_FROM_NAME`
     * из настроек проекта.
     *
     * У обоих необязательных ключей отсутствие и пустая строка — одно и то же.
     * Лишние ключи молча игнорируются: опечатка выглядит как «параметр не
     * сработал», а не как ошибка.
     *
     * Отложенная отправка и очередь — не сюда, это `docs/ru/mail/`.
     *
     * @en
     * Sends a letter right away. Returns `status`, `errorMsg`, `data`; nothing
     * is thrown, the trouble arrives in the answer, and both go to the `mail` log.
     *
     * `$params`: `email`, `subj`, `html`, optional `replyTo` and `fromName`.
     *
     *     MproHelper::sendMail(['email' => $to, 'subj' => 'Order accepted', 'html' => $html]);
     */
    public static function sendMail(array $params): array
    {
        try {

            $res = API_Mail::run('sendNow', [
                'to'       => $params['email'] ?? '',
                'subject'  => $params['subj'] ?? '',
                'html'     => $params['html'] ?? '',
                // Where the answer goes: the site sends from a service address
                // nobody reads, and a form has an address that is read.
                'replyTo'  => $params['replyTo'] ?? '',
                // The name in front of the address. Empty means the one from
                // the settings of the project: the address itself never changes
                // here, only the way it signs itself.
                'fromName' => $params['fromName'] ?? '',
            ]);

            if (!$res['status']) {
                throw new \Exception($res['errorMsg']);
            }

            self::addLog('mail', [
                'status' => true,
                'email'  => $params['email'],
                'subj'   => $params['subj'],
            ]);

            return [
                'status'   => true,
                'errorMsg' => '',
                'data'     => $res['data'] ?? [],
            ];
        } catch (\Throwable $e) {
            // в лог `mail` эту беду уже записал API — там же, где она случилась,
            // и с командой. Второй записью получалась та же строка дважды.
            // Здесь остаётся только ответ вызывающему: исключений этот метод не
            // бросает, страница из-за почты падать не должна
            return [
                'status'   => false,
                'errorMsg' => $e->getMessage(),
                'data'     => [],
            ];
        }
    }

    /**
     * @ru
     * Пишет строку в свой лог. Файл на день и хранится две недели, поэтому на
     * диске он датирован: `storage/logs/<имя>-ГГГГ-ММ-ДД.log`, а `<имя>.log` —
     * только имя, которое даётся ротации.
     *
     * `$logName` — имя лога, оно же имя файла. `$data` — строка или массив;
     * массив разворачивается в строки `ключ: значение`, а вложенный массив —
     * в JSON.
     *
     *     MproHelper::addLog('order', ['id' => $id, 'sum' => $sum]);
     *
     * Исключений не бросает. Не записался лог — права на файл, место на диске —
     * строка уходит в системный лог PHP (`error_log`), а вызывающий работает
     * дальше: задача крона, письмо и страница из-за лога не падают.
     *
     * @en
     * Writes a line into its own log. A file per day, kept for two weeks, so on
     * disk it is dated: `storage/logs/<name>-YYYY-MM-DD.log`; `<name>.log` is
     * only the name given to the rotation.
     *
     * `$logName` — the name of the log and of the file. `$data` — a string or an
     * array; an array is unfolded into `key: value` lines.
     *
     *     MproHelper::addLog('order', ['id' => $id, 'sum' => $sum]);
     *
     * Never throws. A log that cannot be written — file permissions, a full
     * disk — goes to the PHP system log (`error_log`), and the caller goes on:
     * a cron task, a letter or a page do not fall because of a log.
     */
    public static function addLog(string $logName, string|array $data): void
    {
        // имя уходит в путь файла, поэтому из него не должно получиться пути:
        // ни слэша, ни точек. Своё имя тут всегда константа, а вот принесённое
        // из запроса без этой строки писало бы файл где угодно
        $logName = preg_replace('/[^A-Za-z0-9_-]/', '', $logName) ?: 'magic';

        try {
            self::writeLog($logName, $data);
        } catch (\Throwable $e) {
            // файл лога бывает чужим: крон работает от одного пользователя,
            // сайт от другого, и кто первым создал файл дня, тот им и владеет.
            // Беда лога — не беда того, кто пишет, поэтому наружу она не идёт;
            // а чтобы не пропала совсем, строка уходит в системный лог PHP
            @error_log('magicpro addLog(' . $logName . ') failed: ' . $e->getMessage());
        }
    }

    private static function writeLog(string $logName, string|array $data): void
    {
        $logger = new Logger($logName);

        $logger->pushHandler(
            new RotatingFileHandler(
                storage_path("logs/{$logName}.log"),
                14,
                Level::Info
            )
        );

        if (is_array($data)) {
            $text = '';

            foreach ($data as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                }

                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                }

                if ($value === null) {
                    $value = 'null';
                }

                $text .= $key . ': ' . $value . PHP_EOL;
            }

            $data = trim($text);
        }

        $logger->info($data);
    }

    /**
     * @ru
     * Шлёт сообщение в телеграм. Возвращает ответ телеграма как есть, запись
     * уходит в лог `telegram`.
     *
     * `$message` — текст, `$chat_id` — чат, `$botToken` — токен бота,
     * `$mode` — разметка текста: `HTML` или `Markdown`.
     *
     * @en
     * Sends a message to telegram. Returns the answer of telegram as it is, and
     * writes a line into the `telegram` log.
     *
     * `$message` — the text, `$chat_id` — the chat, `$botToken` — the token of
     * the bot, `$mode` — markup of the text: `HTML` or `Markdown`.
     */
    public static function telegramSend(string $message, string $chat_id, string $botToken, string $mode = 'HTML'): array
    {
        $url = 'https://api.telegram.org/bot' . $botToken . '/sendMessage';

        try {
            $response = \Illuminate\Support\Facades\Http::post($url, [
                'chat_id'    => $chat_id,
                'text'       => $message,
                'parse_mode' => $mode,
            ]);
        } catch (\Throwable $e) {
            // сеть не ответила: наружу это уходит тем же массивом, что и отказ
            // телеграма, иначе страница падает из-за недоступного бота
            self::addLog('telegram', [
                'status'  => false,
                'chat_id' => $chat_id,
                'error'   => $e->getMessage(),
            ]);

            return ['ok' => false, 'description' => $e->getMessage()];
        }

        self::addLog('telegram', [
            'status' =>  $response->successful(),
            'chat_id'  => $chat_id,
            'message'   => $message,
        ]);

        $answer = $response->json();

        // телеграм отвечает объектом, но пустое или не-json тело даёт не массив,
        // а тип обещан массивом
        return is_array($answer)
            ? $answer
            : ['ok' => false, 'description' => 'telegram answered ' . $response->status()];
    }


    /**
     * @ru
     * Шифрует массив в строку AES-256-CBC со случайным IV. IV кладётся в начало.
     * Обратно — `decrypt` с тем же ключом.
     *
     * Строка обычная base64, в ней бывают `+`, `/` и `=`. В адрес её класть
     * через `urlencode()`, иначе часть символов потеряется по дороге.
     *
     * Подписи нет: шифр скрывает содержимое, но не доказывает, что его не
     * подменили. Для ссылки подтверждения этого хватает — подобрать ключ, чтобы
     * получился осмысленный json, нельзя, — а вот доверять расшифрованному как
     * подписанному не стоит.
     *
     * `$data` — массив, `$key` — ключ шифрования.
     *
     * Удобно для токенов в ссылках подтверждения: положил в ссылку, получил
     * обратно массив.
     *
     *     $token = MproHelper::crypt(['id' => 42, 'email' => $email], $key);
     *     $data  = MproHelper::decrypt($token, $key);   // ['id' => 42, ...]
     *
     * @en
     * Encrypts an array into an AES-256-CBC string fit for a link or a letter.
     * Back with `decrypt` and the same key.
     *
     * `$data` — the array, `$key` — the key.
     */
    public static function crypt(array $data, string $key): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($json, 'AES-256-CBC', $key, 0, $iv);
        $result = base64_encode($iv . $encrypted);
        return $result;
    }


    /**
     * @ru
     * Разбирает строку от `crypt` обратно в массив. Чужая строка или другой
     * ключ дают пустой массив, исключения нет.
     *
     * `$data` — строка, `$key` — тот же ключ.
     *
     * @en
     * Turns a string of `crypt` back into an array. A foreign string or another
     * key gives an empty array, nothing is thrown.
     *
     * `$data` — the string, `$key` — the same key.
     */
    public static function decrypt(string $data, string $key): array
    {
        $decoded = base64_decode($data, true);

        // короче IV — это не наша строка. Без проверки openssl получал обрезок
        // и ронял warning про длину IV, а обработчик ошибок Laravel умеет
        // превращать warning в исключение: обещание «молча пустой массив»
        // держалось только в консоли
        if ($decoded === false || strlen($decoded) <= 16) {
            return [];
        }

        $decrypted = openssl_decrypt(substr($decoded, 16), 'AES-256-CBC', $key, 0, substr($decoded, 0, 16));

        if ($decrypted === false) {
            return [];
        }

        $result = json_decode($decrypted, true);

        // расшифроваться может и число, и строка: тип обещан массивом
        return is_array($result) ? $result : [];
    }

    /**
     * @ru
     * Печатает что угодно на страницу json-ом, для отладки. Ничего не
     * возвращает.
     *
     * `$var` — что показать, `$showXmp` — обернуть в `<xmp>`; `false` печатает
     * голый json.
     *
     *     {{ MproHelper::dump($item->fields()) }}
     *
     * @en
     * Prints anything onto the page as json, for debugging. Returns nothing.
     *
     * `$var` — what to show, `$showXmp` — wrap it into `<xmp>`; `false` prints
     * bare json.
     *
     *     {{ MproHelper::dump($item->fields()) }}
     */
    public static function dump($var, bool $showXmp = true): void
    {
        try {
            // json_encode молча возвращает false на ресурсе и на битом utf-8:
            // без этой ветки метод печатал пустоту и выглядел сломанным
            $json = json_encode($var, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($json === false) {
                $json = '// ' . json_last_error_msg() . ' (' . get_debug_type($var) . ')';
            }

            if ($showXmp) {
                // `</xmp>` внутри данных закрывает контейнер, и всё после него
                // становится разметкой страницы. Отладочный вывод не повод
                // отдавать страницу тому, чей текст мы печатаем
                echo '<xmp style="line-height:1.2; font-size:12px;">'
                    . str_replace('</xmp', '<\/xmp', $json)
                    . '</xmp>';
            } else {
                echo $json;
            }
        } catch (\Throwable $e) {
            echo '<pre style="color:red">Ошибка дампа: ' . e($e->getMessage()) . '</pre>';
        }
    }

    /**
     * @ru
     * Ресайз по ширине, на лету и с кешем. Исходник не трогается.
     *
     * `$file` — **абсолютный путь в файловой системе**. Путь из поля картинки
     * ленты — это url, его надо развернуть: `public_path($item->img1['path'])`.
     * Один и тот же файл, переданный по-разному, попадёт в разные ветки кеша.
     *
     * `$width` — ширина в px, `$format` и `$quality` без значения берутся из
     * настроек. Шкала четвёртого аргумента зависит от формата: у webp, avif и
     * jpg это качество 10–100, у png — сжатие 0–9.
     *
     * Возвращает `path`, `width`, `height`, `size`, `ms`, `rotated`,
     * `rotateMs`, `cmd`, `errorMsg`. При ошибке `path` пустой, причина в
     * `errorMsg`, заглушка не подставляется.
     *
     *     $img = MproHelper::imageReduceX(public_path($item->img1['path']), 800);
     *
     * Это вход в ресайзер, подробности — `docs/ru/image/use.md`.
     *
     * @en
     * Resize by width, on the fly and cached. The original is not touched.
     *
     * `$file` — an **absolute path in the file system**. A path from an image
     * field of a feed is a url and has to be unfolded:
     * `public_path($item->img1['path'])`.
     *
     * `$width` — width in px, `$format` and `$quality` come from the settings
     * when empty. The scale of the fourth argument depends on the format:
     * quality 10–100 for webp, avif and jpg, compression 0–9 for png.
     *
     * Returns `path`, `width`, `height`, `size`, `ms`, `rotated`, `rotateMs`,
     * `cmd`, `errorMsg`. On an error `path` is empty and the reason is in
     * `errorMsg`; no placeholder is substituted.
     *
     *     $img = MproHelper::imageReduceX(public_path($item->img1['path']), 800);
     */
    public static function imageReduceX(string $file, int $width, ?string $format = null, ?int $quality = null): array
    {
        return ImageJob::make($file, 'x', $width, $format, $quality);
    }

    /**
     * @ru
     * То же, что `imageReduceX`, только по высоте.
     *
     * `$height` — высота в px, остальное как у ресайза по ширине.
     *
     * @en
     * The same as `imageReduceX`, but by height.
     *
     * `$height` — height in px, the rest as in the resize by width.
     */
    public static function imageReduceY(string $file, int $height, ?string $format = null, ?int $quality = null): array
    {
        return ImageJob::make($file, 'y', $height, $format, $quality);
    }

    /**
     * @ru
     * Убирает из кеша все производные одного исходника, любого размера и
     * формата. Возвращает число удалённых файлов.
     *
     * `$file` — абсолютный путь к исходнику, тот же, что даётся ресайзу. Сам
     * файл может быть уже удалён: для поиска производных нужен только путь.
     *
     * @en
     * Removes every derivative of one source from the cache, of any size and
     * format. Returns how many files went.
     *
     * `$file` — absolute path of the source, the same one the resize is given.
     * The file itself may be gone already: only the path is needed.
     */
    public static function imageCacheClear(string $file): int
    {
        return ImageJob::clear($file);
    }

    /**
     * @ru
     * Чистит кеш от производных, у которых не стало исходника. Возвращает
     * `files`, `bytes`, `kept`, `skipped`. Зовётся из крона, через контроллер
     * статьи.
     *
     *     return MproHelper::imageCacheCleanup();
     *
     * @en
     * Sweeps the cache of derivatives whose source is gone. Returns `files`,
     * `bytes`, `kept`, `skipped`. Called from the cron, through a controller of
     * an article.
     *
     *     return MproHelper::imageCacheCleanup();
     */
    public static function imageCacheCleanup(): array
    {
        return ImageJob::cleanup();
    }

    /**
     * @ru
     * Mime картинки по расширению имени: `image/jpeg`, `image/png`,
     * `image/webp`, `image/gif`. Прочее — пустая строка.
     *
     * `$text` — имя файла или путь.
     *
     * @en
     * The mime of an image by the extension of its name: `image/jpeg`,
     * `image/png`, `image/webp`, `image/gif`. Anything else is an empty string.
     *
     * `$text` — a file name or a path.
     */
    public static function imageType(string $text): string
    {
        $text = match (strtolower(pathinfo($text, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => '',
        };

        return $text;
    }

    /**
     * @ru
     * Текст записи ленты, готовый к выводу: разворачивает подстановки `#поле#`
     * и теги magic-компонентов внутри текста.
     *
     * `$item` — запись ленты, `$code` — имя её текстового поля.
     *
     *     {!! MproHelper::feedText($item, 'body') !!}
     *
     * @en
     * The text of a feed record ready to print: unfolds the `#field#` marks and
     * the tags of magic components inside it.
     *
     * `$item` — the record, `$code` — the name of its text field.
     *
     *     {!! MproHelper::feedText($item, 'body') !!}
     */
    public static function feedText(FeedItem $item, string $code): string
    {
        return FeedText::render($item, $code);
    }

    /**
     * @ru
     * Markdown в html для текста, который пишет оператор: html внутри
     * вырезается, ссылки с javascript выбрасываются. Тем и отличается от
     * `getDoc`: тот читает файлы самого пакета и им доверяет.
     *
     * `$md` — исходный markdown.
     *
     * @en
     * Markdown to html for a text written by an operator: html inside is
     * stripped, links carrying javascript are dropped.
     *
     * `$md` — the markdown source.
     */
    public static function mdToHtml(string $md): string
    {
        if (trim($md) === '') {
            return '';
        }

        return Str::markdown($md, [
            'html_input'         => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    // A closed table: what is in it gets translated, the rest is dropped. So the
    // address does not depend on what else arrives — hieroglyphs, emoji,
    // invisible spaces simply will not be in the result.
    //
    // Spaces, dashes and underscores come down to one dash: an underscore is
    // allowed in an address, but the separator of words has to be one.
    private const TRANSLIT_URL = [
        'А' => 'a', 'Б' => 'b', 'В' => 'v', 'Г' => 'g', 'Д' => 'd', 'Е' => 'e',
        'Ё' => 'yo', 'Ж' => 'zh', 'З' => 'z', 'И' => 'i', 'Й' => 'y', 'К' => 'k',
        'Л' => 'l', 'М' => 'm', 'Н' => 'n', 'О' => 'o', 'П' => 'p', 'Р' => 'r',
        'С' => 's', 'Т' => 't', 'У' => 'u', 'Ф' => 'f', 'Х' => 'h', 'Ц' => 'c',
        'Ч' => 'ch', 'Ш' => 'sh', 'Щ' => 'sch', 'Ъ' => '', 'Ы' => 'y', 'Ь' => '',
        'Э' => 'e', 'Ю' => 'yu', 'Я' => 'ya',
        'Ђ' => 'dj', 'Ј' => 'j', 'Љ' => 'lj', 'Њ' => 'nj', 'Ћ' => 'c', 'Џ' => 'dz',
        'Č' => 'c', 'Ć' => 'c', 'Đ' => 'dj', 'Š' => 's', 'Ž' => 'z',

        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e',
        'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k',
        'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r',
        'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c',
        'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
        'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        'ђ' => 'dj', 'ј' => 'j', 'љ' => 'lj', 'њ' => 'nj', 'ћ' => 'c', 'џ' => 'dz',
        'č' => 'c', 'ć' => 'c', 'đ' => 'dj', 'š' => 's', 'ž' => 'z',

        'A' => 'a', 'B' => 'b', 'C' => 'c', 'D' => 'd', 'E' => 'e', 'F' => 'f',
        'G' => 'g', 'H' => 'h', 'I' => 'i', 'J' => 'j', 'K' => 'k', 'L' => 'l',
        'M' => 'm', 'N' => 'n', 'O' => 'o', 'P' => 'p', 'Q' => 'q', 'R' => 'r',
        'S' => 's', 'T' => 't', 'U' => 'u', 'V' => 'v', 'W' => 'w', 'X' => 'x',
        'Y' => 'y', 'Z' => 'z',

        'a' => 'a', 'b' => 'b', 'c' => 'c', 'd' => 'd', 'e' => 'e', 'f' => 'f',
        'g' => 'g', 'h' => 'h', 'i' => 'i', 'j' => 'j', 'k' => 'k', 'l' => 'l',
        'm' => 'm', 'n' => 'n', 'o' => 'o', 'p' => 'p', 'q' => 'q', 'r' => 'r',
        's' => 's', 't' => 't', 'u' => 'u', 'v' => 'v', 'w' => 'w', 'x' => 'x',
        'y' => 'y', 'z' => 'z',

        '0' => '0', '1' => '1', '2' => '2', '3' => '3', '4' => '4',
        '5' => '5', '6' => '6', '7' => '7', '8' => '8', '9' => '9',

        ' ' => '-', '_' => '-', '-' => '-', '–' => '-', '—' => '-',
    ];

    /**
     * @ru
     * Строка в кусок адреса: латиница в нижнем регистре, цифры и дефис. Что не
     * переводится — выбрасывается.
     *
     * `$text` — исходная строка, `$limit` — потолок длины, режется по границе
     * слова; `0` — не резать. Одно слово длиннее лимита режется по лимиту:
     * пустой адрес хуже обрубка.
     *
     *     $slug = MproHelper::translitForUrl($item->title);
     *
     * Таблица закрытая: русский и сербский переводятся, пробелы, дефисы и
     * подчёркивания сводятся к одному дефису, а всё прочее — знаки препинания,
     * кавычки, иероглифы, эмодзи — просто выбрасывается. Из «Как выбрать
     * ноутбук?» получится `kak-vybrat-noutbuk`, а из строки без единой знакомой
     * буквы — пустая.
     *
     * Этим считается `__slug` записи ленты. К именам статей отношения не имеет:
     * там свои правила и свой транслит в браузере.
     *
     * @en
     * A string into a piece of an address: lowercase latin, digits and a dash.
     * What does not translate is dropped.
     *
     * `$text` — the source string, `$limit` — the cap of the length, cut on a
     * word boundary; `0` — do not cut. A single word longer than the limit is
     * cut at the limit: an empty address is worse than a stump.
     *
     *     $slug = MproHelper::translitForUrl($item->title);
     */
    public static function translitForUrl(string $text, int $limit = 200): string
    {
        // «й» может прийти как «и» с отдельным значком: в таком виде таблица его
        // не найдёт и буква пропадёт из адреса
        if (class_exists(\Normalizer::class)) {
            $text = \Normalizer::normalize($text, \Normalizer::FORM_C) ?: $text;
        }

        $result = '';

        foreach (preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $char) {
            $result .= self::TRANSLIT_URL[$char] ?? '';
        }

        $result = trim((string) preg_replace('/-+/', '-', $result), '-');

        if ($limit > 0 && mb_strlen($result) > $limit) {
            // сразу за лимитом дефис — значит, резали ровно по границе, и
            // последнее слово целое. Раньше отрезалось и оно
            $onBoundary = mb_substr($result, $limit, 1) === '-';

            $result = mb_substr($result, 0, $limit);

            // хвост до последнего дефиса — обрубок слова, он в адресе не нужен.
            // Дефиса нет — слово одно, и оно остаётся обрубком: пустой адрес
            // хуже
            $lastDash = mb_strrpos($result, '-');

            if (! $onBoundary && $lastDash !== false) {
                $result = mb_substr($result, 0, $lastDash);
            }

            $result = trim($result, '-');
        }

        return $result;
    }

    /**
     * @ru
     * Чистый текст из размеченного: снимает теги и entity, выбрасывает эмодзи,
     * сводит пробелы и переносы к одному пробелу. Для description и анонсов.
     *
     * `$text` — исходный текст, `$limit` — потолок длины, режется по границе
     * слова; `0` — не резать.
     *
     *     $descr = MproHelper::trimAndCutText($item->body, 160);
     *
     * @en
     * Plain text out of marked-up: takes off tags and entities, drops emoji,
     * brings spaces and line breaks down to one space. For descriptions and
     * announcements.
     *
     * `$text` — the source text, `$limit` — the cap of the length, cut on a word
     * boundary; `0` — do not cut.
     *
     *     $descr = MproHelper::trimAndCutText($item->body, 160);
     */
    public static function trimAndCutText(string $text, int $limit = 0): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = strip_tags($text);
        // символы-картинки и то, что к ним лепится: модификаторы цвета кожи,
        // селекторы начертания и склейка ZWJ. Без них от «A👍🏽B» оставался
        // невидимый модификатор между буквами
        $text = preg_replace('/[\p{So}\p{Cn}\x{1F3FB}-\x{1F3FF}\x{FE0E}\x{FE0F}\x{200D}\x{20E3}]/u', '', $text);
        $text = preg_replace('/\s+/u', ' ', $text); // \r \n \t и лишние пробелы
        $text = trim($text);

        if ($limit > 0 && mb_strlen($text) > $limit) {
            // берём на символ больше, чтобы увидеть, попал ли разрез в середину
            // слова, и срезаем это слово целиком
            $cut  = mb_substr($text, 0, $limit + 1);
            $text = (string) preg_replace('/\s+\S*$/u', '', $cut);

            // слово длиннее потолка целиком: резать по границе не по чему, и
            // тогда режем по потолку — обещание есть обещание
            if ($text === '' || mb_strlen($text) > $limit) {
                $text = mb_substr($cut, 0, $limit);
            }
        }

        return rtrim($text);
    }
    /**
     * @ru
     * Родительская статья. Пустой массив у корня и у несуществующего id.
     *
     * `$id` — id статьи.
     *
     * @en
     * The parent article. An empty array for the root and for an id that is not
     * there.
     *
     * `$id` — id of the article.
     */
    public static function getParent(int $id): array
    {
        $parentId = Article::query()
            ->where('id', $id)
            ->value('parentId');

        if (empty($parentId)) {
            return [];
        }

        return self::getArtById((int) $parentId);
    }


    /**
     * @ru
     * Дети статьи по её id, в порядке `npp`. Только те, у кого стоит `menuOn`:
     * это меню. Поля `id`, `title`, `name`, `menuOn`, `updated_at`, `npp`.
     *
     * `$artId` — id родителя.
     *
     * @en
     * The children of an article by its id, in `npp` order. Only those with
     * `menuOn`: this is a menu. Fields `id`, `title`, `name`, `menuOn`,
     * `updated_at`, `npp`.
     *
     * `$artId` — id of the parent.
     */
    public static function getChildrenById(int $artId): array
    {
        return Article::query()
            ->select('id', 'title', 'name', 'menuOn', 'updated_at', 'npp')
            ->where('parentId', $artId)
            ->where('menuOn', true)
            ->orderBy('npp')
            ->get()
            ->toArray();
    }

    /**
     * @ru
     * То же меню, но родитель ищется по имени статьи. Обычный вход из блейда.
     *
     * `$name` — имя статьи-родителя.
     *
     *     $menu = MproHelper::getChildrenByName('topMenu');
     *
     * @en
     * The same menu, but the parent is found by the name of the article. The
     * usual entry from a blade.
     *
     * `$name` — name of the parent article.
     *
     *     $menu = MproHelper::getChildrenByName('topMenu');
     */
    public static function getChildrenByName(string $name): array
    {
        $id = Article::select('id')->where('name', $name)->value('id');

        $result = Article::query()
            ->select('id', 'title', 'name', 'menuOn', 'updated_at')
            ->where('parentId', $id)
            ->where('menuOn', true)
            ->orderBy('npp')
            ->get()
            ->toArray();
        return $result;
    }

    /**
     * @ru
     * Статья по имени, все её поля. Нет такой — пустой массив.
     *
     * `$name` — имя статьи, оно же её адрес.
     *
     * @en
     * An article by its name, all of its fields. Nothing found — an empty array.
     *
     * `$name` — the name of the article, which is also its address.
     */
    public static function getArtByName(string $name): array
    {
        $result = Article::query()
            ->where('name', $name)
            ->select('*')
            ->get()
            ->toArray();
        return $result[0] ?? [];
    }

    /**
     * @ru
     * Статья по id, все её поля. Нет такой — пустой массив.
     *
     * `$id` — id статьи.
     *
     * @en
     * An article by id, all of its fields. Nothing found — an empty array.
     *
     * `$id` — id of the article.
     */
    public static function getArtById(int $id): array
    {
        $result = Article::query()
            ->where('id', $id)
            ->select('*')
            ->get()
            ->toArray();
        return $result[0] ?? [];
    }

    /**
     * @ru
     * Путь от корня до статьи — массив имён по порядку, для хлебных крошек.
     * Оборванная цепочка отдаёт то, что успело собраться. Кольцо в `parentId`
     * тоже: обход останавливается, второй раз в ту же статью не заходит.
     *
     * `$id` — id статьи.
     *
     * @en
     * The path from the root down to an article — an array of names in order,
     * for breadcrumbs. A broken chain gives back what was collected, and so
     * does a ring in `parentId`: the walk never enters the same article twice.
     *
     * `$id` — id of the article.
     */
    public static function getPathToRootById(int $id): array
    {
        $path = [];
        $seen = [];

        try {
            // кольцо A → B → A модель разрешает: она запрещает только ссылку на
            // самого себя. Раньше обход шёл до сотого шага и возвращал путь из
            // повторяющихся имён; теперь на втором заходе в ту же статью он
            // останавливается и отдаёт то, что успел собрать
            while (! isset($seen[$id])) {
                $seen[$id] = true;

                $article = Article::findOrFail($id);
                array_unshift($path, $article->name);

                if ($article->parentId == 0) {
                    break;
                }

                $id = $article->parentId;
            }
        } catch (\Throwable) {
            return $path;
        }

        return $path;
    }
}
