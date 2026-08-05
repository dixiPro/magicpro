<?php

use MagicProDatabaseModels\Article;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use MagicProSrc\Api\API_Auth;
use MagicProSrc\Config\MagicGlobals;
use MagicProSrc\Mail\API_Mail;
use Monolog\Logger;
use Monolog\Level;
use Monolog\Handler\RotatingFileHandler;


class MproHelper
{
    // language the documentation is written in, the one translations fall back to
    private const DOC_SOURCE_LANG = 'ru';

    // Documentation page rendered to html. The markdown source lives in the package
    // docs directory, one subdirectory per language: docs/ru/routing.md. Without an
    // explicit language the one from the MagicPro settings is used.
    // Both parts of the path are validated, they come from route parameters.
    // With $renderHtml = false the markdown source is returned untouched, which is
    // what a download or a translation job needs.
    public static function getDoc(string $name, string $lang = '', bool $renderHtml = true): string
    {
        if ($lang === '') {
            $lang = (string) (MagicGlobals::$INI['LANGUAGE'] ?? 'ru');
        }

        if (!preg_match('/^[A-Za-z0-9_-]+$/', $name) || !preg_match('/^[A-Za-z-]+$/', $lang)) {
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

    // Public site key of Google reCAPTCHA, the one that goes into the form and
    // is visible in the page source. Kept here so blades never read env directly
    // and the key lives in .env only.
    public static function getRecaptureKey(): string
    {
        return (string) env('RECAPTCHA_SITE_KEY');
    }

    // The secret key is read from RECAPTCHA_SECRET_KEY inside the api and is
    // never passed in. run() is used instead of runOrFail() to keep the bool
    // result the calling articles expect.
    public static function verifyRecapture(string $response): bool
    {
        return API_Auth::run('checkGoogleCapture', [
            'token' => $response,
        ])['status'];
    }

    public static function sendMail(array $params): array
    {
        try {

            $res = API_Mail::run('sendNow', [
                'to'      => $params['email'] ?? '',
                'subject' => $params['subj'] ?? '',
                'html'    => $params['html'] ?? '',
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


    public static function crypt(array $data, string $key): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($json, 'AES-256-CBC', $key, 0, $iv);
        $result = base64_encode($iv . $encrypted);
        return $result;
    }


    public static function decrypt(string $data, string $key): array
    {
        $decoded = base64_decode($data);
        $iv = substr($decoded, 0, 16);
        $encrypted = substr($decoded, 16);
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
        $result = $decrypted ? json_decode($decrypted, true) : [];
        return $result;
    }

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

    // Markdown of a feed record rendered to html. Unlike getDoc, the source here
    // is written by an operator, so raw html inside it is stripped rather than
    // passed through, and links that carry javascript are dropped.
    // The admin preview calls this through the api, the site calls it directly,
    // so both show the same thing.
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

    //
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
    // Parent article of the given one. Empty array for the root and for a missing id.
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


    //  Получить всех потомков записи.
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

    //  Получить всех потомков записи.
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

    public static function getArtByName(string $name): array
    {
        $result = Article::query()
            ->where('name', $name)
            ->select('*')
            ->get()
            ->toArray();
        return $result[0] ?? [];
    }

    public static function getArtById(int $id): array
    {
        $result = Article::query()
            ->where('id', $id)
            ->select('*')
            ->get()
            ->toArray();
        return $result[0] ?? [];
    }

    // 
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
