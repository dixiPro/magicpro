<?php

namespace MagicProSrc;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Vite;
use Illuminate\Http\Request;
use Livewire\Mechanisms\ComponentRegistry;

use MagicProSrc\LivewireComponentRegistry; // magicLiveWare

use MagicProDatabaseModels\MagicProUser; // таблица авторизации админки

use MagicProAdminMiddleware\CheckMagicAuth; // миддлваре авторизации

use Illuminate\Support\Facades\Config;

use MagicProSrc\Console\InstallMagicProCommand; // Создание директорий при установке

use MagicProSrc\Config\MagicGlobals; // Глобальные константы

class MagicServiceProvider extends ServiceProvider
{
    public function boot(): void
    {

        MagicGlobals::register(); // Константы глобальные

        // php artisan magicpro:install
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallMagicProCommand::class,
            ]);
        }

        // вьюхи
        $this->loadViewsFrom(MAGIC_VIEW_DIR, 'magic');


        // Include helper functions
        require_once __DIR__ . '/TreeHelper.php';
        require_once __DIR__ . '/DumpHelper.php';

        // админка
        // Load admin routes with "web" middleware
        Route::middleware('web')->group(function () {
            $this->loadRoutesFrom(__DIR__ . '/../admin/web.php');
        });

        // Load admin views
        $this->loadViewsFrom(__DIR__ . '/../admin/views', 'magicAdmin');

        // Load migrations from the package
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Авторизация админки
        // guard и provider динамически
        Config::set('auth.providers.magic_users', [
            'driver' => 'eloquent',
            'model' => MagicProUser::class,
        ]);

        Config::set('auth.guards.magic', [
            'driver' => 'session',
            'provider' => 'magic_users',
        ]);

        // Blade директива  @mproauth            
        Blade::if('mproauth', function () {
            return Auth::guard('magic')->check();
        });

        // Регистрируем middleware под алиасом 'magic.auth'
        app('router')->aliasMiddleware('magic.auth', CheckMagicAuth::class);

        // Vite скрипты
        // Laravel будет искать dev-сервер по нашему hot-файлу
        Vite::useHotFile(storage_path('magicpro.vite.hot'));

        // И манифест/ассеты — в public/vendor/magicpro (как в проде)
        Vite::useBuildDirectory('vendor/magicpro');
        // vite
        $this->publishes([
            __DIR__ . '/../public' => public_path('vendor/magicpro'),
        ], 'magicpro-assets');
        // 


        // Register anonymous Blade components from the given path
        Blade::anonymousComponentPath(MAGIC_VIEW_DIR, 'magic');
        // //
        Blade::componentNamespace('MagicProControllers', 'magic');
    }

    public function register(): void
    {
        // Override Livewire's default ComponentRegistry with a custom implementation
        // Example: register a Livewire component manually (currently commented out)
        // <livewire:magic::articleName />

        $this->app->extend(ComponentRegistry::class, fn($r, $app) => new LivewireComponentRegistry($app));
    }
}
