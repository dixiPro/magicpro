<?php

namespace MagicProAdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use MagicProSrc\Config\MagicGlobals; // global constants
use MagicProSrc\MagicFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use MagicProSrc\MagicLang;


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
                'restoreParams'      => ['name' => 'restoreParams'],


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
    private function restoreParams(): array
    {

        if (!File::delete(base_path(MagicGlobals::$localIniFile))) {
            throw new \RuntimeException('Error deleting file ' . base_path(MagicGlobals::$localIniFile));
        }
        return [];
    }


    // 📋 saved results
    private function getCrawlerResults(Request $request): array
    {
        $path  = base_path(MagicGlobals::$magicStorageDir) . "/crawlerResult.json";
        if (!File::exists($path)) {
            return ['result' => ''];
        }
        $data = File::get($path);
        return ['result' => $data];
    }

    // 📋 saved results
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



    // 📋 read parameters
    private function getDirStatus(Request $request): array
    {
        $publicDir  = base_path(MagicGlobals::$INI['STATIC_HTML_DIR']) . "/";
        $storageDir = STATIC_HTML_CREATE_DIR  . "/";
        return [
            'storageDirStatus' =>  is_dir($storageDir),
            'publicDirStatus' =>  is_dir($publicDir)
        ];
    }

    private function startHtmlCache(Request $request): array
    {

        $this->deleteFromPublic();

        $from = STATIC_HTML_CREATE_DIR . "/";
        $to =  base_path(MagicGlobals::$INI['STATIC_HTML_DIR']) . "/";
        $res = File::copyDirectory($from, $to);
        if (!$res) {
            throw new \InvalidArgumentException("copy error");
        }
        return [];
    }

    private function processUrl(Request $request): array
    {
        //  declared before try so it can be returned in catch if needed
        $body = '';
        $saveStatus = false;
        $url = '';

        try {
            $url = $request->input('url');
            $saveToFile = $request->input('saveToFile') ?? false;

            $res = Http::withOptions([
                'verify' => false,     // ssl may sometimes fail
                'timeout' => 3,
                'follow_redirects' => false,
            ])->get($url);

            if ($res->status() !== 200) {
                throw new \InvalidArgumentException($res->status());
            }
            // save
            if (
                $saveToFile  &&
                $body !== false && // body exists 
                str_starts_with($res->header('Content-Type'), 'text')
            ) {
                $body = $res->body();
                $this->saveHtmlFile($url, $body); // will throw an exception on error
                $saveStatus = true;
            }

            return [
                'check' => true,
                'code'  => 200,
                'saveStatus' => $saveStatus,
                'body'  => $body,
                'url'   => $url,
            ];
        } catch (\Throwable $th) {
            return [
                'check' => false,
                'code'  => $th->getMessage(),
                'saveStatus' => $saveStatus,
                'body'  => $body,
                'url'   => $url ?? '',
            ];
        }
    }

    private function saveHtmlFile(string $url, string $body): void
    {
        // take only the path (without protocol, domain, and parameters)
        $path = parse_url($url, PHP_URL_PATH) ?? '/';
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $ext = $ext ? $ext : 'html';


        // root page
        if ($path === '/' || $path === '' || $path === null) {
            $path = '/index';
        }
        // will throw an exception on error
        MagicFile::make()
            ->dir(STATIC_HTML_CREATE_DIR)
            ->name($path)
            ->ext($ext)
            ->put($body);
    }

    // 📋 read parameters
    private function getIniParams(Request $request): array
    {
        return  MagicGlobals::$INI;
    }

    // 📋 read parameters
    private function getParamsAttr(): array
    {
        $schema = require MagicGlobals::$dataSchema;
        foreach ($schema as $key => $value) {
            $schema[$key]['label'] = MagicLang::getMsg($value['label']);
        }
        return  $schema;
    }

    // save parameters
    private function saveIniParams(Request $request): array
    {
        $allVars = $request->input('allVars');
        return  MagicGlobals::saveIniFile($allVars);
    }
    // 
    // delete from storage
    private function deleteFromStorage(): array
    {
        $dir = STATIC_HTML_CREATE_DIR;

        if (is_dir($dir)) {
            $res = $dir  = File::deleteDirectory($dir);
            if (!$res) {
                throw new \InvalidArgumentException("storage delete error");
            }
        }

        $path  = base_path(MagicGlobals::$magicStorageDir) . "/crawlerResult.json";
        if (File::exists($path)) {
            unlink($path);
        }
        return [];
    }
    // 
    // delete from public
    private function deleteFromPublic(): array
    {
        $dir  = base_path(MagicGlobals::$INI['STATIC_HTML_DIR']) . "/";

        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0777, true);
        }

        $res  = File::deleteDirectory($dir);
        if (!$res) {
            throw new \InvalidArgumentException("public delete error");
        }
        return [];
    }
}
