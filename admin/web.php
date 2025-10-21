<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Csrf;
use Illuminate\Support\Facades\DB;


// Админка
use MagicProAdminControllers\AdminController;

Route::get('/a_dmin', [AdminController::class, 'index']);
Route::get('/a_dmin/artList', [AdminController::class, 'artList']);
Route::get('/a_dmin/adminList', [AdminController::class, 'adminList']);

Route::get('/a_dmin/api/clearCache', [AdminController::class, 'clearCache'])
    ->middleware('magic.auth')
    ->withoutMiddleware([Csrf::class])->name('magic.cleatCahe');


//
Route::get('/a_dmin/artEditor', function () {
    return view('magicAdmin::artEditor');
})->where('any', '.*');


// Апи Артикл
use MagicProAdminControllers\API_ArticlesPostController;

Route::post('/a_dmin/api/articles', [API_ArticlesPostController::class, 'handle'])
    ->middleware('magic.auth')
    ->withoutMiddleware([Csrf::class]);


// файл редактирование админов    
use MagicProAdminControllers\API_EditUsersController;

Route::post('/a_dmin/api/editUsers', [API_EditUsersController::class, 'handle'])
    ->middleware('magic.auth:admin')
    ->withoutMiddleware([Csrf::class]);

// файл менеджер АПИ    
use MagicProAdminControllers\API_FileManagerPostController;

Route::post('/a_dmin/api/fileManager', [API_FileManagerPostController::class, 'handle'])
    ->middleware('magic.auth')
    ->withoutMiddleware([Csrf::class]);


// авторизация Мпро
use MagicProAdminControllers\AuthController;

Route::post('/a_dmin/login', [AuthController::class, 'login'])->name('magic.login');
Route::get('/a_dmin/logout', [AuthController::class, 'logout'])->name('magic.logout');

//     
//
//
use MagicProSrc\Routing\DynamicRouteHandler;

Route::any('{any?}', [DynamicRouteHandler::class, 'handle'])
    ->where('any', '.*');
