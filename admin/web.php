<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Csrf;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use MagicProSrc\Config\MagicGlobals; // Глобальные константы


// Админка
use MagicProAdminControllers\AdminController;



Route::get('/a_dmin', [AdminController::class, 'index'])->name('magic.a_dmin');

// Сетап
Route::get('/a_dmin/setup', function () {
    return view('magicAdmin::setup');
})->name('magic.setup');

// import_tab
Route::get('/a_dmin/import_tab', function () {
    return view('magicAdmin::import_tab');
})->name('magic.import_tab');

// export
Route::get('/a_dmin/export_tab', function () {
    return view('magicAdmin::export_tab');
})->name('magic.export_tab');

// список статей
Route::get('/a_dmin/artList', [AdminController::class, 'artList'])->name('magic.artList');

// очистить кэш
Route::get('/a_dmin/api/clearCache', [AdminController::class, 'clearCache'])
    ->middleware('magic.auth')
    ->withoutMiddleware([Csrf::class])->name('magic.cleatCahe');

// тест записи    
Route::get('/a_dmin/api/testWrite', [AdminController::class, 'testWrite'])
    ->middleware('magic.auth')
    ->withoutMiddleware([Csrf::class])->name('magic.testWrite');

// phpInfo
Route::get('/a_dmin/phpinfo', function () {
    phpinfo();
})->middleware('magic.auth')->withoutMiddleware([Csrf::class]);


// Импорт экспорт
use MagicProAdminControllers\ImportExportController;
// импорт
Route::post('/a_dmin/importArticle', [ImportExportController::class, 'importArticle'])
    ->withoutMiddleware([Csrf::class])->name('magic.importArticle');

// экспорт
Route::get('/a_dmin/api/exportArticle', [ImportExportController::class, 'exportArticle'])
    ->middleware('magic.auth')
    ->withoutMiddleware([Csrf::class])->name('magic.exportArticle');

// Апи статьи 
use MagicProAdminControllers\API_ArticlesPostController;

Route::post('/a_dmin/api/articles', [API_ArticlesPostController::class, 'handle'])
    ->middleware('magic.auth')
    ->withoutMiddleware([Csrf::class]);

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
    ->withoutMiddleware([Csrf::class]);


// страница паука
Route::get('/a_dmin/crawler', function () {
    return view('magicAdmin::crawler');
})->where('any', '.*')->name('magic.crawler');

Route::any('/a_shop/adminer', function () {
    require __DIR__ . '/controller/adminer/index.php';
})->middleware(['web', 'magic.auth'])
    ->withoutMiddleware([Csrf::class])
    ->name('magic.dataBase');


// список админов
Route::get('/a_dmin/adminList', [AdminController::class, 'adminList'])->name('magic.admin_list');

// апи админов
use MagicProAdminControllers\API_EditUsersController;

// API редактирования юзеров доступна только админу
Route::post('/a_dmin/api/editUsers', [API_EditUsersController::class, 'handle'])
    ->middleware('magic.auth:admin')
    ->withoutMiddleware([Csrf::class]);


// API настройки
use MagicProAdminControllers\API_Setup;

Route::post('/a_dmin/api/setup', [API_Setup::class, 'handle'])
    ->middleware('magic.auth:admin')
    ->withoutMiddleware([Csrf::class]);

// авторизация Мпро
use MagicProAdminControllers\AuthController;

Route::post('/a_dmin/login', [AuthController::class, 'login'])->name('magic.login');
Route::get('/a_dmin/logout', [AuthController::class, 'logout'])->name('magic.logout');

// переадрессаця стандартного логина
Route::get('/login', function () {
    return redirect('/');
})->name('login');


Route::get('a_dmin/download-db', function () {
    $path = base_path('database/database.sqlite');
    return response()->download($path, 'db.sqlite');
})
    ->middleware('magic.auth:admin')
    ->name('magic.downloadDb');
//     
//
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
Route::any('{any?}', [DynamicRouteHandler::class, 'handle'])
    ->where('any', $pattern)->withoutMiddleware([Csrf::class]);
