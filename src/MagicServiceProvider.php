<?php

namespace MagicProSrc;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Vite;
use Illuminate\Http\Request;
use Livewire\Mechanisms\ComponentRegistry;

use MagicProSrc\Livewire\LivewireComponentRegistry;

use Illuminate\Foundation\AliasLoader;

use MagicProDatabaseModels\MagicProUser; // таблица авторизации админки
use MagicProDatabaseModels\Feed; // лента
use MagicProDatabaseModels\FeedItem; // запись ленты

use MagicProAdminMiddleware\CheckMagicAuth; // миддлваре авторизации

use Illuminate\Support\Facades\Config;

use MagicProSrc\Config\MagicGlobals; // Глобальные константы
use Illuminate\Console\Scheduling\Schedule;
use MagicProSrc\Scheduling\MagicProSchedule;

use MagicProSrc\MagicLang;

use MagicProSrc\Console\AdminCommand;                            // команда создания админа
use MagicProSrc\Console\DocsCommand;                             // сборка доки из phpdoc

use MagicProSrc\Console\Aws\SetupCommand as AwsSetupCommand;     // настройка AWS
use MagicProSrc\Console\Aws\RemoveCommand as AwsRemoveCommand;   // удаление ключа или пользователя
use MagicProSrc\Console\Aws\StatusCommand as AwsStatusCommand;   // состояние AWS

use MagicProSrc\Lenta\FeedPathGenerator; // папка картинок лент внутри диска
use Spatie\MediaLibrary\Support\PathGenerator\PathGeneratorFactory;

class MagicServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Картинки лент — в подпапку magicFeed, а не в корень диска.
        //
        // Глобальный генератор путей в медиатеке один на всё приложение, и
        // забирать его себе нельзя: рядом может жить другой пакет с медиатекой,
        // и тогда кто загрузился первым — тот и решает, где лежат чужие файлы.
        // Регистрируем свой генератор на свою модель, он проверяется раньше
        // глобального.
        PathGeneratorFactory::setCustomPathGenerators(
            FeedItem::class,
            FeedPathGenerator::class
        );

        // вьюхи
        $this->loadViewsFrom(MAGIC_VIEW_DIR, 'magic');


        // Include helper functions
        require_once __DIR__ . '/Helpers/MproHelper.php';
        // совместимость ...
        require_once __DIR__ . '/Helpers/TreeHelper.php';
        require_once __DIR__ . '/Helpers/DumpHelper.php';

        // Feed models under short names, so that blades and article controllers
        // write Feed:: and FeedItem:: instead of the full namespace. The alias
        // loader resolves them lazily, on first use.
        AliasLoader::getInstance()->alias('Feed', Feed::class);
        AliasLoader::getInstance()->alias('FeedItem', FeedItem::class);

        // Маршруты пакета — routes/. Порядок — часть контракта: dynamic.php
        // последний, его `{any?}` забирает всё, что зарегистрировано после.
        //
        // МСП — вне группы web: агенту не нужны сессия и куки, а CSRF отбил бы
        // его POST-запросы
        $this->loadRoutesFrom(__DIR__ . '/../routes/mcp.php');

        Route::middleware('web')->group(function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/admin.php');   // /a_dmin
            $this->loadRoutesFrom(__DIR__ . '/../routes/site.php');    // публичные адреса пакета
            $this->loadRoutesFrom(__DIR__ . '/../routes/dynamic.php'); // статьи сайта, последним
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
        class_exists('API_SiteAuth', false) || class_alias(
            \MagicProSrc\Api\API_SiteAuth::class,
            'API_SiteAuth'
        );
        class_exists('API_Users', false) || class_alias(
            \MagicProSrc\Api\API_Users::class,
            'API_Users'
        );

        // шедулер

        $this->callAfterResolving(
            Schedule::class,
            function (Schedule $schedule): void {
                app(MagicProSchedule::class)->register($schedule);
            }
        );

        // консольные команды пакета
        if ($this->app->runningInConsole()) {
            $this->commands([
                AdminCommand::class,
                DocsCommand::class,
                AwsSetupCommand::class,
                AwsRemoveCommand::class,
                AwsStatusCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        MagicGlobals::register(); // Константы глобальные

        // почта из .env через config(): env() вне конфига после config:cache
        // пуст, см. src/Config/magicMail.php
        $this->mergeConfigFrom(__DIR__ . '/Config/magicMail.php', 'magicpro_mail');
        MagicLang::loadLocale(MagicGlobals::$INI['LANGUAGE']);
    }
}
