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

use MagicProSrc\Config\MagicGlobals; // Глобальные константы
use Illuminate\Console\Scheduling\Schedule;
use MagicProSrc\Scheduling\MagicProSchedule;

use MagicProSrc\MagicLang;

class MagicServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // вьюхи
        $this->loadViewsFrom(MAGIC_VIEW_DIR, 'magic');


        // Include helper functions
        require_once __DIR__ . '/Helpers/MproHelper.php';

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

        // Override Livewire's default ComponentRegistry with a custom implementation
        // <livewire:magic::articleName />
        $this->app->extend(ComponentRegistry::class, fn($r, $app) => new LivewireComponentRegistry($app));

        // guard: boot() может вызываться повторно (тесты PHPUnit поднимают
        // приложение заново), а class_alias на второй раз падает
        // "name already in use". Создаём алиас один раз. См. TODO в MagicGlobals.
        class_exists('API_Auth', false) || class_alias(
            \MagicProSrc\Api\API_Auth::class,
            'API_Auth'
        );

        // шедулер

        $this->callAfterResolving(
            Schedule::class,
            function (Schedule $schedule): void {
                app(MagicProSchedule::class)->register($schedule);
            }
        );
    }

    public function register(): void
    {
        MagicGlobals::register(); // Константы глобальные
        MagicLang::loadLocale(MagicGlobals::$INI['LANGUAGE']);
    }
}
