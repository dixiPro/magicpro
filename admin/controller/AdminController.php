<?php

namespace MagicProAdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use MagicProDatabaseModels\Article;
use MagicProDatabaseModels\MagicProUser;
use MagicProSrc\MagicLang;

use MagicProAdminControllers\API_ArticlesPostController;
use MagicProSrc\Config\MagicGlobals; // global constants

class AdminController extends Controller
{
    public function index()
    {

        $messages = [];

        try {
            // 1. Проверить/создать MAGIC_DATA_DIR
            if (!is_dir(MAGIC_DATA_DIR)) {
                if (!mkdir(MAGIC_DATA_DIR, 0775, true)) {
                    throw new \Exception('Cannot create dir: ' . MAGIC_DATA_DIR);
                }
                $messages[] = MagicLang::getMsg('install_start');
                $messages[] = MagicLang::getMsg('install_dir_created') . ': ' . MAGIC_DATA_DIR;

                // Проверить права: записать и удалить тестовый файл
                $testFile = MAGIC_DATA_DIR . DIRECTORY_SEPARATOR . 'write_test.tmp';
                File::put($testFile, 'test');
                File::delete($testFile);
                $messages[] = MagicLang::getMsg('install_write_ok');

                // Перегенирация статий
                $res = API_ArticlesPostController::run(['command' => 'regenerateAll']);
                if ($res['status']) {

                    $messages[] = MagicLang::getMsg('regenerate_articles');
                    $messages = array_merge($messages, $res['data']);
                } else {
                    # code...
                    $messages[] = $res['errorMsg'];
                }
            }

            // 2. Проверить/создать VENDOR_PUBLIC и скопировать файлы если папки не было или версия изменилась
            $versionFile = VENDOR_PUBLIC . '/version.txt';
            $installedVersion = is_file($versionFile) ? trim(file_get_contents($versionFile)) : null;
            $needsCopy = !is_dir(VENDOR_PUBLIC) || $installedVersion !== MAGIC_VERSION;

            if ($needsCopy) {
                if (!is_dir(VENDOR_PUBLIC)) {
                    if (!mkdir(VENDOR_PUBLIC, 0775, true)) {
                        throw new \Exception('Cannot create dir: ' . VENDOR_PUBLIC);
                    }
                }
                File::copyDirectory(VENDOR_FROM, VENDOR_PUBLIC);
                file_put_contents($versionFile, MAGIC_VERSION);
                $messages[] = MagicLang::getMsg('install_files_copied') . ': ' . VENDOR_PUBLIC;
                $messages[] = MagicLang::getMsg('install_version_updated') . ': ' . MAGIC_VERSION;
            }

            // 2. Проверить/создать PUBLIC_UPLOAD_DIR
            $startDir = public_path(MagicGlobals::$INI['PUBLIC_UPLOAD_DIR']);
            if (!is_dir($startDir)) {
                if (!mkdir($startDir, 0775, true)) {
                    throw new \Exception('Cannot create dir: ' . VENDOR_PUBLIC);
                }
            }
        } catch (\Throwable $e) {
            $messages[] = MagicLang::getMsg('install_error') . ': ' . $e->getMessage();
        }

        // packages\dixipro\magicpro\admin\views\index.blade.php
        return view('magicAdmin::index', compact('messages'));
    }

    public function testWrite()
    {
        $testArray = MAGIC_FILE_ROLES;
        $operation = '';

        try {
            foreach ($testArray as &$item) {
                $dir = $item['value'];

                // создание директории
                if (!File::isDirectory($dir)) {
                    $operation = "create directory $dir";
                    File::ensureDirectoryExists($dir, 0775, true);
                }

                $timestamp = time();
                $file = $dir . DIRECTORY_SEPARATOR . "{$timestamp}_testfile.txt";

                // запись
                $operation = "write to file $file";
                File::put($file, $timestamp);

                // удаление
                $operation = "delete file $file";
                File::delete($file);

                $item['result'] = 'ok';
            }
        } catch (\Throwable $th) {
            $item['result'] = "$operation — " . $th->getMessage();
        }

        return redirect()->back()->with('testWriteStatus', $testArray);
    }

    public function clearCache()
    {
        $clearCacheStatus = [
            'cache'  => Artisan::call('cache:clear'),
            'config' => Artisan::call('config:clear'),
            'route'  => Artisan::call('route:clear'),
            'view'   => Artisan::call('view:clear'),
            'event'  => Artisan::call('event:clear'),
        ];

        return redirect()->back()->with('clearCacheStatus', $clearCacheStatus);
    }

    public function artList()
    {
        $articles = Article::orderBy('parentId')->orderBy('name')->get();
        return view('magicAdmin::artList', compact('articles'));
    }

    public function adminList()
    {
        $users = MagicProUser::orderBy('email')->get();
        return view('magicAdmin::adminList', compact('users'));
    }
}
