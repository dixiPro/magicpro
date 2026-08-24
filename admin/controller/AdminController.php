<?php

namespace MagicProAdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
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
     * Вся работа в Installer, здесь только показать, что он сказал: сперва
     * беды, потом список пройденных проверок. Раньше отдавались только беды, и
     * пустой экран приходилось понимать как «всё хорошо».
     */
    public function index()
    {
        $installer = new Installer();

        return view('magicAdmin::index', [
            'report' => $installer->run(),
            'okList' => $installer->okList(),
        ]);
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
