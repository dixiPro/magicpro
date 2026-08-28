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


class MproHelper
{
    // language the documentation is written in, the one translations fall back to
    private const DOC_SOURCE_LANG = 'ru';

    /**
     * @ru
     * Страница документации пакета в html, источник — markdown в `docs/<язык>/`.
     *
     * `$name` — имя файла без `.md`, можно с папкой: `main/use`.
     * `$lang` — пустой берётся из настроек; нет перевода — покажется ru.
     * `$renderHtml` — `false` отдаёт markdown как есть.
     *
     *     {!! MproHelper::getDoc('feed/use') !!}
     *
     * @en
     * A documentation page of the package as html, the source is markdown in
     * `docs/<lang>/`.
     *
     * `$name` — file name without `.md`, a folder is allowed: `main/use`.
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
     *     MproHelper::sendMail(['email' => $to, 'subj' => 'Заказ', 'html' => $html]);
     *
     * @en
     * Sends a letter right away. Returns `status`, `errorMsg`, `data`; nothing
     * is thrown, the trouble arrives in the answer, and both go to the `mail` log.
     *
     * `$params`: `email`, `subj`, `html`, optional `replyTo` and `fromName`.
     *
     *     MproHelper::sendMail(['email' => $to, 'subj' => 'Order', 'html' => $html]);
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

            self::addLog('mail', [
                'status' => false,
                'error'  => $e->getMessage(),
                'email'  => $params['email'] ?? '',
                'subj'   => $params['subj'] ?? '',
            ]);

            return [
                'status'   => false,
                'errorMsg' => $e->getMessage(),
                'data'     => [],
            ];
        }
    }

    /**
     * @ru
     * Пишет строку в свой лог `storage/logs/<имя>.log`, файл на день, хранится
     * две недели.
     *
     * `$logName` — имя лога, оно же имя файла. `$data` — строка или массив;
     * массив разворачивается в строки `ключ: значение`.
     *
     *     MproHelper::addLog('order', ['id' => $id, 'sum' => $sum]);
     *
     * @en
     * Writes a line into its own log, `storage/logs/<name>.log`, a file per day,
     * kept for two weeks.
     *
     * `$logName` — the name of the log and of the file. `$data` — a string or an
     * array; an array is unfolded into `key: value` lines.
     *
     *     MproHelper::addLog('order', ['id' => $id, 'sum' => $sum]);
     */
    public static function addLog(string $logName, string|array $data): void
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

        $response = \Illuminate\Support\Facades\Http::post($url, [
            'chat_id'    => $chat_id,
            'text'       => $message,
            'parse_mode' => $mode,
        ]);

        self::addLog('telegram', [
            'status' =>  $response->successful(),
            'chat_id'  => $chat_id,
            'message'   => $message,
        ]);

        return $response->json();
    }


    /**
     * @ru
     * Шифрует массив в строку AES-256-CBC, пригодную для ссылки или письма.
     * Обратно — `decrypt` с тем же ключом.
     *
     * `$data` — массив, `$key` — ключ шифрования.
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
        $decoded = base64_decode($data);
        $iv = substr($decoded, 0, 16);
        $encrypted = substr($decoded, 16);
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
        $result = $decrypted ? json_decode($decrypted, true) : [];
        return $result;
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
            if ($showXmp) {
                echo '<xmp style="line-height:1.2; font-size:12px;">'
                    . json_encode($var, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    . '</xmp>';
            } else {
                echo json_encode($var, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        } catch (\Throwable $e) {
            echo '<pre style="color:red">Ошибка дампа: ' . '</pre>';
        }
    }

    /**
     * @ru
     * Ресайз по ширине, на лету и с кешем. Возвращает массив `path`, `x`, `y`,
     * `mime`, `size`; исходник не трогается.
     *
     * `$file` — путь от корня сайта, `$width` — ширина в px,
     * `$format` и `$quality` — пустые берутся из настроек.
     *
     *     $img = MproHelper::imageReduceX($item->img1['path'], 800);
     *
     * @en
     * Resize by width, on the fly and cached. Returns `path`, `x`, `y`, `mime`,
     * `size`; the original is not touched.
     *
     * `$file` — path from the root of the site, `$width` — width in px,
     * `$format` and `$quality` — empty ones come from the settings.
     *
     *     $img = MproHelper::imageReduceX($item->img1['path'], 800);
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
     * `$file` — путь к исходнику, тот же, что даётся ресайзу.
     *
     * @en
     * Removes every derivative of one source from the cache, of any size and
     * format. Returns how many files went.
     *
     * `$file` — path of the source, the same one the resize is given.
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
     * вырезается, ссылки с javascript выбрасываются.
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
     * слова; `0` — не резать.
     *
     *     $slug = MproHelper::translitForUrl($item->title);
     *
     * @en
     * A string into a piece of an address: lowercase latin, digits and a dash.
     * What does not translate is dropped.
     *
     * `$text` — the source string, `$limit` — the cap of the length, cut on a
     * word boundary; `0` — do not cut.
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
            $result = mb_substr($result, 0, $limit);

            // хвост до последнего дефиса — обрубок слова, он в адресе не нужен
            $lastDash = mb_strrpos($result, '-');

            if ($lastDash !== false) {
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
        $text = preg_replace('/[\p{So}\p{Cn}]/u', '', $text);
        $text = preg_replace('/\s+/u', ' ', $text); // \r \n \t и лишние пробелы
        $text = trim($text);

        if ($limit > 0 && mb_strlen($text) > $limit) {
            $text = mb_substr($text, 0, $limit + 1);
            $text = preg_replace('/\s+\S*$/u', '', $text);
        }

        return $text;
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
     * Оборванная цепочка отдаёт то, что успело собраться.
     *
     * `$id` — id статьи.
     *
     * @en
     * The path from the root down to an article — an array of names in order,
     * for breadcrumbs. A broken chain gives back what was collected.
     *
     * `$id` — id of the article.
     */
    public static function getPathToRootById(int $id): array
    {
        $path = [];
        $count = 0;


        try {
            while (++$count < 100) {
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
