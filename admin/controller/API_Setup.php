<?php

namespace MagicProAdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use MagicProDatabaseModels\MagicProUser;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use MagicProSrc\Config\MagicGlobals; // Глобальные константы



class API_Setup extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        try {
            $methods = [
                'getIniParams'   => ['name' => 'getIniParams'],
                'saveIniParams'  => ['name' => 'saveIniParams'],
                'saveKey'        => ['name' => 'saveKey'],
                'startHtmlCache' => ['name' => 'startHtmlCache'],
                'stopHtmlCache'  => ['name' => 'stopHtmlCache'],
                'processUrl'     => ['name' => 'processUrl'],
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
        if (str_ends_with($host, MagicGlobals::$INI['HOST_DEV'])) {
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
            // $this->saveHtmlFile($url, $body);
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

        $saveDir = public_path(MagicGlobals::$INI['STATIC_HTML_CREATE_DIR']);
        $saveDir = rtrim($saveDir, '/');

        // Корневая страница
        if ($path === '/' || $path === '' || $path === null) {
            $path = '/index';
        }
        $path = rtrim($path, '/');
        $fullPath = $saveDir . $path . '.html';
        // Создать директорию если её нет
        mkdir(dirname($fullPath), 0777, true);

        // Пишем файл
        file_put_contents($fullPath, $body);
    }

    // 📋 считать параметры
    private function getIniParams(Request $request): array
    {
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
        $old = public_path(MagicGlobals::$INI['STATIC_HTML_DIR']);
        $new = public_path(MagicGlobals::$INI['STATIC_HTML_CREATE_DIR']);

        // проверка готового кеша
        if (!file_exists($new)) {
            throw new \Exception("Нет папки " . $new);
        }
        // старая папка есть
        if (file_exists($old)) {
            delete_file($old);
        }
        rename($new, $old);

        MagicGlobals::saveKey('STATIC_HTML_ENABLE',  true);

        return [];
    }
    // остановить хтмл кеш
    private function stopHtmlCache(Request $request): array
    {
        $html = public_path(MagicGlobals::$INI['STATIC_HTML_DIR']);
        delete_file($html);
        MagicGlobals::saveKey('STATIC_HTML_ENABLE',  true);
        return [];
    }
}
