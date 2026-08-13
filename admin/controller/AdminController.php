<?php

namespace MagicProAdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use MagicProDatabaseModels\Article;
use MagicProDatabaseModels\MagicProUser;
use MagicProSrc\Install\Installer;

class AdminController extends Controller
{
    /** Сколько записей на странице списка статей. */
    private const ART_PER_PAGE = 50;

    /**
     * Главная админки: она же установка и она же проверка.
     *
     * Вся работа в Installer, здесь только показать, что он сказал. Пусто —
     * значит всё на месте и говорить не о чем.
     */
    public function index()
    {
        return view('magicAdmin::index', [
            'report' => (new Installer())->run(),
        ]);
    }

    public function testWrite()
    {
        $testArray = MAGIC_FILE_ROLES;

        // try внутри цикла: каталоги друг от друга не зависят, и ошибка одного
        // не должна оставлять остальные без ответа
        foreach ($testArray as $index => $item) {
            $dir = $item['value'];
            $operation = '';

            try {
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

                $testArray[$index]['result'] = 'ok';
            } catch (\Throwable $th) {
                $testArray[$index]['result'] = "$operation — " . $th->getMessage();
            }
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
        $articles = Article::orderBy('name')
            ->orderBy('updated_at')
            ->paginate(self::ART_PER_PAGE);

        return view('magicAdmin::artList', compact('articles'));
    }

    public function adminList()
    {
        $users = MagicProUser::orderBy('email')->get();

        return view('magicAdmin::adminList', compact('users'));
    }
}
