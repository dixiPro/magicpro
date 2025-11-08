<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Csrf;
use Illuminate\Support\Facades\DB;


use MagicProAdminControllers\AdminController;

Route::get('/a_dmin', [AdminController::class, 'handle']);

Route::get('/a_dmin/artEditor', function () {
    return view('magicAdmin::artEditor');
})->where('any', '.*');


use MagicProAdminControllers\API_ArticlesPostController;

Route::post('/a_dmin/api/articles', [API_ArticlesPostController::class, 'handle'])
    ->withoutMiddleware([Csrf::class]);

use MagicProAdminControllers\API_FileManagerPostController;

Route::post('/a_dmin/api/fileManager', [API_FileManagerPostController::class, 'handle'])
    ->withoutMiddleware([Csrf::class]);


// 
