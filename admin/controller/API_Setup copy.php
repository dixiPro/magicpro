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


                'getCrawlerResults'  => ['name' => 'getCrawlerResults'],
                'saveCrawlerResults' => ['name' => 'saveCrawlerResults'],
                'getDirStatus'       => ['name' => 'getDirStatus'],
                'getIniParams'       => ['name' => 'getIniParams'],
                'getParamsAttr'      => ['name' => 'getParamsAttr'],
                'saveIniParams'      => ['name' => 'saveIniParams'],
                'deleteFromPublic'   => ['name' => 'deleteFromPublic'],
                'deleteFromStorage'  => ['name' => 'deleteFromStorage'],
                'processUrl'         => ['name' => 'processUrl'],
                'startHtmlCache'     => ['name' => 'startHtmlCache'],


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
    // 📋 сохраненные результаты
    private function getCrawlerResults(Request $request): array
    {
        $path  = base_path(MagicGlobals::$magicStorageDir) . "/crawlerResult.json";
        if (!File::exists($path)) {
            return ['result' => ''];
        }
        $data = File::get($path);
        return ['result' => $data];
    }

    // 📋 сохраненные результаты
    private function saveCrawlerResults(Request $request): array
    {
        $savedData = $request->input('savedData');
        MagicFile::make()
            ->base()
            ->dir(MagicGlobals::$magicStorageDir)
            ->name('crawlerResult.json')
            ->put($savedData);
        return [];
    }



    // 📋 считать параметры
    private function getDirStatus(Request $request): array
    {
        $publicDir  = base_path(MagicGlobals::$INI['STATIC_HTML_DIR']) . "/";
        $storageDir = base_path(MagicGlobals::$INI['STATIC_HTML_CREATE_DIR'])  . "/";
        return [
            'storageDirStatus' =>  is_dir($storageDir),
            'publicDirStatus' =>  is_dir($publicDir)
        ];
    }

    private function startHtmlCache(Request $request): array
    {

        $this->deleteFromPublic();

        $from = base_path(MagicGlobals::$INI['STATIC_HTML_CREATE_DIR']) . "/";
        $to =  base_path(MagicGlobals::$INI['STATIC_HTML_DIR']) . "/";
        $res = File::copyDirectory($from, $to);
        if (!$res) {
            throw new \InvalidArgumentException("Ошибка копирования");
        }
        return [];
    }


    private function processUrl(Request $request): array
    {
        try {
            $url = $request->input('url');
            $saveWithDot = $request->input('saveWithoutChecking') ?? false;

            // === 1. Файл или нет ===
            $path   = parse_url($url, PHP_URL_PATH) ?? '';
            $isFile = str_contains($path, '.') || $saveWithDot;

            // === 2. Resolve ===
            $host = parse_url($url, PHP_URL_HOST);

            $resolve  = [];
            $hostDev  = MagicGlobals::$INI['HOST_DEV'];
            $saveFile = false;

            if (str_ends_with($host, $hostDev)) {
                $resolve[] = "$host:80:192.168.1.33";
                $resolve[] = "$host:443:192.168.1.33";
                $saveFile  = true;
            }

            // === 3. CURL ===
            $ch = curl_init($url);

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                //CURLOPT_RESOLVE        => $resolve,
                CURLOPT_ENCODING       => '',
                CURLOPT_NOBODY         => $isFile,                // если файл — тело не нужно
                CURLOPT_CUSTOMREQUEST  => $isFile ? 'HEAD' : 'GET', // HEAD для файлов
            ]);

            // ВАЖНО: всегда выполняем запрос
            $curlResult = curl_exec($ch);

            // тело только если НЕ файл
            $body = $isFile ? '' : $curlResult;

            $code        = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?? '';

            curl_close($ch);

            // === 4. Проверка UTF-8 (только для HTML, не файлов) ===
            if (
                !$isFile &&
                $body !== false &&
                !mb_check_encoding($body, 'UTF-8')
            ) {
                throw new \Exception("Ответ не UTF-8");
            }

            // === 5. Сохранение HTML (только dev, только 200, только text/html) ===
            if (
                !$isFile &&
                $saveFile && // принадлежит хосту 
                $code === 200 && // существует 
                $body !== false && // тело есть 
                str_starts_with($contentType, 'text/html')

            ) {
                $this->saveHtmlFile($url, $body);
            }

            return [
                'check' => ($code >= 200 && $code < 400),
                'code'  => $code,
                'body'  => $body,
                'url'   => $url,
            ];
        } catch (\Throwable $th) {
            return [
                'check' => false,
                'code'  => $th->getMessage(),
                'body'  => '',
                'url'   => $url ?? '',
            ];
        }
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
        return  MagicGlobals::$INI;
    }

    // 📋 считать параметры
    private function getParamsAttr(): array
    {
        $schema = require MagicGlobals::$dataSchema;;
        return  $schema;
    }

    // сохранить параметры
    private function saveIniParams(Request $request): array
    {
        $allVars = $request->input('allVars');
        return  MagicGlobals::saveIniFile($allVars);
    }
    // 
    // Удалить из стораджа
    private function deleteFromStorage(): array
    {
        $dir = base_path(MagicGlobals::$INI['STATIC_HTML_CREATE_DIR'])  . "/";

        if (is_dir($dir)) {
            $res = $dir  = File::deleteDirectory($dir);
            if (!$res) {
                throw new \InvalidArgumentException("Ошибка удаления стораджа");
            }
        }

        $path  = base_path(MagicGlobals::$magicStorageDir) . "/crawlerResult.json";
        if (File::exists($path)) {
            unlink($path);
        }
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
        return [];
    }
}
