<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use MagicProSrc\Config\MagicGlobals; // Глобальные константы
use illuminate\foundation\http\middleware\preventrequestforgery;

// Админка
use MagicProAdminControllers\AdminController;


// 12 и 13 версии
$csrf = class_exists(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class)
    ? \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class
    : \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class;

Route::get('/a_dmin', [AdminController::class, 'index'])->name('magic.a_dmin');

// Документация: phpdoc хелперов, читается из исходника
Route::get('/a_dmin/documentation', function () {
    return view('magicAdmin::documentation');
})->name('magic.documentation');

// MCP: работа с AI-агентом, который ходит в локальный MCP
use MagicProSrc\Ai\API_Ai;

// страница
Route::get('/a_dmin/mcp', function () {
    return view('magicAdmin::mcp');
})->name('magic.mcp');

// АПИ
Route::post('/a_dmin/api/mcp', [API_Ai::class, 'handle'])
    ->middleware('magic.auth')
    ->withoutMiddleware([$csrf]);

// Другое: витрина иконок и прочее по мелочи
Route::get('/a_dmin/other', function () {
    return view('magicAdmin::other');
})->name('magic.other');

// Сетап
Route::get('/a_dmin/setup', function () {
    return view('magicAdmin::setup');
})->name('magic.setup');

// import_tab
Route::get('/a_dmin/import_tab', function () {
    return view('magicAdmin::import_tab');
})->name('magic.import_tab');

// laravelUsers
// страница
Route::get('/a_dmin/laravelUsers', function () {
    return view('magicAdmin::users');
})->name('magic.users');

// АПИ
// Апи статьи 
use MagicProSrc\Api\API_Auth;

Route::post('/a_dmin/api/laravelUsers', [API_Auth::class, 'handle'])
    ->middleware('magic.auth')
    ->withoutMiddleware([$csrf]);


// Почтовая система
use MagicProSrc\Mail\API_Mail;

// страница
Route::get('/a_dmin/mailSystem', function () {
    return view('magicAdmin::mailSystem');
})->name('magic.mail');

// АПИ
Route::post('/a_dmin/api/mailSystem', [API_Mail::class, 'handle'])
    ->middleware('magic.auth')
    ->withoutMiddleware([$csrf]);

// Крон: задачи расписания из админки
use MagicProSrc\Scheduling\API_Cron;

// страница
Route::get('/a_dmin/cron', function () {
    return view('magicAdmin::cron');
})->name('magic.cron');

// АПИ
Route::post('/a_dmin/api/cron', [API_Cron::class, 'handle'])
    ->middleware('magic.auth')
    ->withoutMiddleware([$csrf]);

// Ленты
use MagicProSrc\Lenta\API_Feeds;

// страница
Route::get('/a_dmin/feed', function () {
    return view('magicAdmin::feed');
})->name('magic.feed');

// АПИ
Route::post('/a_dmin/api/feed', [API_Feeds::class, 'handle'])
    ->middleware('magic.auth')
    ->withoutMiddleware([$csrf]);



// список статей
Route::get('/a_dmin/artList', [AdminController::class, 'artList'])->name('magic.artList');

// очистить кэш
Route::get('/a_dmin/api/clearCache', [AdminController::class, 'clearCache'])
    ->middleware('magic.auth')
    ->withoutMiddleware([$csrf])->name('magic.cleatCahe');

// phpInfo
Route::get('/a_dmin/phpinfo', function () {
    phpinfo();
})->middleware('magic.auth')->withoutMiddleware([$csrf]);


// Импорт экспорт
use MagicProAdminControllers\ImportExportController;
use MagicProSrc\Import\API_Import;
use MagicProSrc\Import\Snapshots;

// импорт: проверка, работа и сохранённые состояния
Route::post('/a_dmin/api/import', [API_Import::class, 'handle'])
    ->middleware('magic.auth')
    ->withoutMiddleware([$csrf]);

// скачать сохранённое состояние
Route::get('/a_dmin/api/importSnapshot', function (Request $request) {
    return response()->download(Snapshots::path((string) $request->input('file')));
})->middleware('magic.auth')->name('magic.importSnapshot');

// проверка статей: прогон и отчёты
use MagicProSrc\Cleanup\API_Cleanup;
use MagicProSrc\Cleanup\CleanupReport;

Route::post('/a_dmin/api/cleanup', [API_Cleanup::class, 'handle'])
    ->middleware('magic.auth')
    ->withoutMiddleware([$csrf]);

// отчёт лежит в private, поэтому отдаётся отсюда, а не ссылкой на файл
Route::get('/a_dmin/api/cleanupReport', function (Request $request) {
    return response()->file(CleanupReport::path((string) $request->input('file')), [
        'Content-Type' => 'text/html; charset=utf-8',
    ]);
})->middleware('magic.auth')->name('magic.cleanupReport');

// экспорт
Route::get('/a_dmin/api/exportArticle', [ImportExportController::class, 'exportArticle'])
    ->middleware('magic.auth')
    ->withoutMiddleware([$csrf])->name('magic.exportArticle');

// Апи статьи 
use MagicProAdminControllers\API_ArticlesPostController;

Route::post('/a_dmin/api/articles', [API_ArticlesPostController::class, 'handle'])
    ->middleware('magic.auth')
    ->withoutMiddleware([$csrf]);

// страница редактирования статьи
Route::get('/a_dmin/artEditor', function () {
    return view('magicAdmin::artEditor');
})->where('any', '.*')->name('magic.artEditor');

// редактор файлов
Route::get('/a_dmin/fileManager', function () {
    return view('magicAdmin::fileManager');
})->where('any', '.*')->name('magic.fileManager');

// файл менеджер АПИ    
use MagicProAdminControllers\API_FileManagerPostController;

Route::post('/a_dmin/api/fileManager', [API_FileManagerPostController::class, 'handle'])
    ->middleware('magic.auth')
    ->withoutMiddleware([$csrf]);


// страница паука
Route::get('/a_dmin/crawler', function () {
    return view('magicAdmin::crawler');
})->where('any', '.*')->name('magic.crawler');

Route::any('/a_shop/adminer', function () {
    require __DIR__ . '/controller/adminer/index.php';
})->middleware(['web', 'magic.auth'])
    ->withoutMiddleware([$csrf])
    ->name('magic.dataBase');


// список админов
Route::get('/a_dmin/adminList', [AdminController::class, 'adminList'])->name('magic.admin_list');

// апи админов
use MagicProAdminControllers\API_EditUsersController;

// API редактирования админов мппро доступна только админу
Route::post('/a_dmin/api/editUsers', [API_EditUsersController::class, 'handle'])
    ->middleware('magic.auth:admin')
    ->withoutMiddleware([$csrf]);


// API настройки
use MagicProAdminControllers\API_Setup;

Route::post('/a_dmin/api/setup', [API_Setup::class, 'handle'])
    ->middleware('magic.auth:admin')
    ->withoutMiddleware([$csrf]);

// авторизация Мпро
use MagicProAdminControllers\AuthController;

Route::post('/a_dmin/login', [AuthController::class, 'login'])->name('magic.login');
Route::get('/a_dmin/logout', [AuthController::class, 'logout'])->name('magic.logout');

// переадрессаця стандартного логина
Route::get('/login', function () {
    return redirect('/');
})->name('login');
//
//
// AWS SES/SNS webhook
use MagicProSrc\Mail\AwsHookHandler;

Route::post('/awsHook', [AwsHookHandler::class, 'handle'])
    ->withoutMiddleware([$csrf])
    ->name('magic.awsHook');

// Динамический раут
use MagicProSrc\Routing\DynamicRouteHandler;

// 🚫 Сегменты, которые не должны попадать в динамический роутинг
// 🧩 Формируем регулярку: отрицательное совпадение (всё, кроме этих)

//  удаление стартовых и завершающих слешей
$removeStartSlash = array_map(function ($route) {
    return trim($route, '/');
}, MagicGlobals::$INI['EXCLUDED_ROUTES']);

$pattern = '^(?!(' . implode('|', array_map('preg_quote', $removeStartSlash)) . ')).*$';


// ⚙️ Динамический маршрут
// Route::any('{any?}', [DynamicRouteHandler::class, 'handle'])
//     ->where('any', $pattern)->withoutMiddleware([Csrf::class]);

Route::any('{any?}', [DynamicRouteHandler::class, 'handle'])
    ->where('any', $pattern)
    ->withoutMiddleware([$csrf]);
