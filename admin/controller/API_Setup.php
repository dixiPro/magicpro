<?php

namespace MagicProAdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use MagicProSrc\Config\MagicGlobals; // Глобальные константы
use MagicProSrc\MagicFile;
use Illuminate\Support\Facades\File;


class API_Setup extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        try {
            $methods = [
                'getIniParams'       => ['name' => 'getIniParams'],
                'saveIniParams'      => ['name' => 'saveIniParams'],
                'saveKey'            => ['name' => 'saveKey'],
                'deleteFromPublic'   => ['name' => 'deleteFromPublic'],
                'deleteFromStorage'  => ['name' => 'deleteFromStorage'],
                'processUrl'         => ['name' => 'processUrl'],
                'startHtmlCache'     => ['name' => 'startHtmlCache'],
                'listCacheFiles'     => ['name' => 'listCacheFiles'],


            ];

            $command = $request->string('command')->toString();

            if (!array_key_exists($command, $methods)) {
                throw new \InvalidArgumentException("Unknown command '{$command}'");
            }

            $methodName = $methods[$command]['name'];
            if (!method_exists($this, $methodName)) {
                throw new \BadMethodCallException("Method {$methodName} not found");
            }

            $data = $this->{$methodName}($request);

            return response()->json([
                'status'  => true,
                'data'    => $data,
                'request' => $request->all(),
            ]);
        } catch (\Throwable $th) {
            $msg = $th->getMessage();
            if ($th->getFile()) $msg .= ' in ' . $th->getFile();
            if ($th->getLine()) $msg .= ' on line ' . $th->getLine();

            return response()->json([
                'status'   => false,
                'errorMsg' => $msg,
                'request'  => $request->all(),
            ]);
        }
    }

    // ================================
    private function processUrl(Request $request): array
    {
        $url = $request->input('url');

        $host = parse_url($url, PHP_URL_HOST);

        $resolve = [];
        $hostDev = MagicGlobals::$INI['HOST_DEV'];
        $saveFile = false;
        if (str_ends_with($host, $hostDev)) {
            $resolve[] = "$host:80:192.168.1.33";
            $resolve[] = "$host:443:192.168.1.33";
            $saveFile = true;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_RESOLVE => $resolve,
        ]);

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?? '';

        curl_close($ch);

        if (
            $saveFile && $code === 200 &&  $body !== false && str_starts_with($contentType, 'text/html')
        ) {
            $this->saveHtmlFile($url, $body);
        }

        return [
            'check' => ($code >= 200 && $code < 400),
            'code'   => $code,
            'body' => $body,
            'url' => $url
        ];
    }
    private function saveHtmlFile(string $url, string $body): void
    {
        // Берём только path (без протокола, домена и параметров)
        $path = parse_url($url, PHP_URL_PATH) ?? '/';

        // Корневая страница
        if ($path === '/' || $path === '' || $path === null) {
            $path = '/index';
        }

        MagicFile::make()
            ->base()
            ->dir(MagicGlobals::$INI['STATIC_HTML_CREATE_DIR'])
            ->name($path)
            ->ext('html')
            ->put($body);
    }

    // 📋 считать параметры
    private function getIniParams(Request $request): array
    {
        $a = MagicGlobals::$INI;
        return  MagicGlobals::$INI;
    }
    // сохранить параметры
    private function saveIniParams(Request $request): array
    {
        $allVars = $request->string('allVars')->toArray();
        return  MagicGlobals::saveIniFile($allVars);
    }
    // сохранить один ключ
    private function saveKey(Request $request): array
    {
        $key = $request->string('key')->toString();
        $value = $request->string('value')->toString();
        return  MagicGlobals::saveKey($key, $value);
    }
    // запустить хмтл кеш
    private function startHtmlCache(Request $request): array
    {

        $this->deleteFromPublic();

        $from = base_path(MagicGlobals::$INI['STATIC_HTML_CREATE_DIR']) . "/";
        $to =  base_path(MagicGlobals::$INI['STATIC_HTML_DIR']) . "/";
        $res = File::copyDirectory($from, $to);
        if (!$res) {
            throw new \InvalidArgumentException("Ошибка копирования");
        }
        MagicGlobals::saveKey('STATIC_HTML_ENABLE',  true);

        return [];
    }
    // 
    // Удалить из стораджа
    private function deleteFromStorage(): array
    {

        $dir = base_path(MagicGlobals::$INI['STATIC_HTML_CREATE_DIR'])  . "/";

        if (!is_dir($dir)) {
            $res = $dir  = File::deleteDirectory($dir);
            if (!$res) {
                throw new \InvalidArgumentException("Ошибка удаления стораджа");
            }
        }
        MagicGlobals::saveKey('STATIC_HTML_ENABLE',  false);
        return [];
    }
    // 
    // Удалить из публика
    private function deleteFromPublic(): array
    {
        $dir  = base_path(MagicGlobals::$INI['STATIC_HTML_DIR']) . "/";

        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0777, true);
        }

        $res  = File::deleteDirectory($dir);
        if (!$res) {
            throw new \InvalidArgumentException("Ошибка удаления паблик");
        }
        MagicGlobals::saveKey('STATIC_HTML_ENABLE',  false);
        return [];
    }

    public static function listCacheFiles(): array
    {
        $dir = base_path(MagicGlobals::$INI['STATIC_HTML_CREATE_DIR'])  . "/";

        if (!is_dir($dir)) {
            return [];
        }

        $result = [];
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;

            $path = $dir . '/' . $item;

            if (is_file($path)) {
                $result[] = $item;
            }

            if (is_dir($path)) {
                foreach (self::listCacheFiles($path) as $sub) {
                    $result[] = $item . '/' . $sub;
                }
            }
        }
        sort($result);
        return $result;
    }
}
