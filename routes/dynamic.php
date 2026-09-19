<?php

/**
 * LOADED LAST. The pages of the site: every address not taken by a route
 * registered before this file goes to DynamicRouteHandler.
 *
 * Laravel tries routes in the order they were registered, so all the routes of
 * the package already win over the catch-all by being earlier. EXCLUDED_ROUTES
 * is for the opposite case — routes registered after the package by other
 * packages and by the application (livewire, telescope, horizon): at the moment
 * this file is loaded they do not exist yet, so they have to be listed by hand.
 */

use Illuminate\Support\Facades\Route;
use MagicProSrc\Config\MagicGlobals;

$csrf = MagicGlobals::csrfMiddleware();

use MagicProSrc\Routing\DynamicRouteHandler;

// 🚫 Сегменты, которые не должны попадать в динамический роутинг
// 🧩 Формируем регулярку: отрицательное совпадение (всё, кроме этих)

//  удаление стартовых и завершающих слешей
$removeStartSlash = array_map(function ($route) {
    return trim($route, '/');
}, MagicGlobals::$INI['EXCLUDED_ROUTES']);

// Пустой список исключений давал `^(?!()).*$`: пустая группа совпадает в начале
// любой строки, отрицание всегда проваливается, и динамический маршрут не
// подходил ни к одному адресу — сайт отвечал 404 везде.
//
// И граница сегмента: без неё исключение `admin` выключало не только `/admin`,
// но и `/administrator`, и любую статью, чьё имя с него начинается.
$removeStartSlash = array_filter($removeStartSlash, static fn ($route) => $route !== '');

$pattern = $removeStartSlash
    ? '^(?!(' . implode('|', array_map('preg_quote', $removeStartSlash)) . ')(/|$)).*$'
    : '.*';


// ⚙️ Динамический маршрут
// Route::any('{any?}', [DynamicRouteHandler::class, 'handle'])
//     ->where('any', $pattern)->withoutMiddleware([Csrf::class]);

Route::any('{any?}', [DynamicRouteHandler::class, 'handle'])
    ->where('any', $pattern)
    ->withoutMiddleware([$csrf]);
