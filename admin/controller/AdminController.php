<?php

namespace MagicProAdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use MagicProDatabaseModels\Article;
use MagicProDatabaseModels\MagicProUser;

class AdminController extends Controller
{
    public function index()
    {
        return view('magicAdmin::index');
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
        $articles = Article::orderBy('parentId')->orderBy('npp')->get();
        return view('magicAdmin::artList', compact('articles'));
    }

    public function adminList()
    {
        $users = MagicProUser::orderBy('email')->get();
        return view('magicAdmin::adminList', compact('users'));
    }
}
