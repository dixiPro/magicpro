<?php

use MagicProDatabaseModels\Article;
use Illuminate\Support\Facades\Mail;
use Monolog\Logger;
use Monolog\Level;
use Monolog\Handler\RotatingFileHandler;


class MproHelper
{

    public static function verifyRecapture(string $response, string $secret): bool
    {
        $url = 'https://www.google.com/recaptcha/api/siteverify';

        //
        $res = Http::asForm()->post($url, [
            'secret'   => $secret,
            'response' => $response,
        ]);

        if (!$res->successful()) {
            return false;
        }

        $data = $res->json();

        return ($data['success'] ?? false) === true;
    }

    public static function sendMail(array $params): array
    {
        try {

            $required = ['email', 'subj', 'html'];

            foreach ($required as $field) {
                if (empty($params[$field])) {
                    throw new \Exception($field . ' require');
                }
            }

            Mail::html($params['html'], function ($message) use ($params) {

                $message->to($params['email']);
                $message->subject($params['subj']);

                $configSet = env('AWS_SES_CONFIGURATION_SET');

                if ($configSet) {
                    $message->getSymfonyMessage()->getHeaders()
                        ->addTextHeader('X-SES-CONFIGURATION-SET', $configSet);
                }
            });

            self::addLog('mail', [
                'status' => true,
                'email'  => $params['email'],
                'subj'   => $params['subj'],
            ]);

            return [
                'status'   => true,
                'errorMsg' => '',
                'data'     => [],
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

    public static function telegramSend(string $message, string $chat_id, string $botToken, string $mode = 'HTML'): bool
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

        return $response->successful();
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
    // продитель
    public static function getParent(int $id): array
    {
        // TODO: запрос к БД, вернуть parent
        return ['parent' => 'родитель'];
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
