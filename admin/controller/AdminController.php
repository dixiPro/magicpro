<?php

namespace MagicProAdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use MagicProDatabaseModels\Article;
use MagicProDatabaseModels\MagicProUser;
use MagicProSrc\Install\Installer;

class AdminController extends Controller
{
    /** Сколько записей на странице списка статей. */
    private const ART_PER_PAGE = 50;

    /**
     * What the list of articles may be sorted by.
     *
     * The key is what comes in the address, the value is the column. The list
     * is closed: orderBy() gets what is written here and never what came from
     * the browser.
     */
    private const ART_SORT = [
        'name'  => 'name',
        'title' => 'title',
        'id'    => 'id',
        'last'  => 'updated_at',
    ];

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

    public function artList(Request $request)
    {
        $sort = (string) $request->input('sort', 'name');
        $dir  = $request->input('dir') === 'desc' ? 'desc' : 'asc';

        if (! isset(self::ART_SORT[$sort])) {
            $sort = 'name';
        }

        // Id as the second key: equal titles and equal dates would otherwise
        // wander between the pages.
        $articles = Article::orderBy(self::ART_SORT[$sort], $dir)
            ->orderBy('id')
            ->paginate(self::ART_PER_PAGE)
            ->withQueryString();

        return view('magicAdmin::artList', compact('articles', 'sort', 'dir'));
    }

    public function adminList()
    {
        $users = MagicProUser::orderBy('email')->get();

        return view('magicAdmin::adminList', compact('users'));
    }
}
