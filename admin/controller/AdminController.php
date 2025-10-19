<?php

namespace MagicProAdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use MagicProDatabaseModels\Article;
use MagicProDatabaseModels\MagicProUser;

class AdminController extends Controller
{
    public function index()
    {
        $user = [
            'get_current_user' => get_current_user(),
            'whoami' => exec('whoami')
        ];
        $MAGIC_FILE_ROLES = [];


        foreach (MAGIC_FILE_ROLES as $item) {
            $path = $item['value'];
            $exists = file_exists($path);

            $stat = $exists ? stat($path) : null;

            $MAGIC_FILE_ROLES[] = [
                'value' => $item['value'],
                'desc'  => $item['desc'] ?? '',
                'stat'  => $exists ? [
                    'owner' => posix_getpwuid($stat['uid'])['name'] ?? $stat['uid'],
                    'group' => posix_getgrgid($stat['gid'])['name'] ?? $stat['gid'],
                    'perms' => substr(sprintf('%o', $stat['mode']), -4),
                ] : null,
            ];
        }

        return view('magicAdmin::index', [
            'MAGIC_FILE_ROLES' => $MAGIC_FILE_ROLES,
            'user' => $user
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

        return redirect()->back()->with('clearCacheStatus', $clearCacheStatus);;
    }


    public function artList()
    {
        // при необходимости упорядочим: сначала npp, потом date по убыванию
        $articles = Article::orderBy('parentId')->orderBy('npp')->get();

        return view('magicAdmin::artList', compact('articles'));
    }


    public function adminList()
    {
        // при необходимости упорядочим: сначала npp, потом date по убыванию
        $users = MagicProUser::orderBy('email')->get();

        return view('magicAdmin::adminList', compact('users'));
    }
}
