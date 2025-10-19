<?php

use Illuminate\Support\Facades\Route;
/*start11*/

Route::get('/testSimple{any?}/', function (\Illuminate\Http\Request $request, $any = null) {
    $request->attributes->add([
        'name' => 'testSimple',
        'title' => 'Тестирование 3',
        'artId' => 11,
        'parentId' => 1,
        'view'  => 'magic::testSimple',
    ]);
    return (new \MagicProControllers\testSimple)->handle($request, $any);
})->where('any', '.*');



Route::get('/test3{any?}/', function (\Illuminate\Http\Request $request, $any = null) {
    $request->attributes->add([
        'name' => 'test3',
        'title' => 'Тестирование 3',
        'artId' => 11,
        'parentId' => 1,
        'view'  => 'magic::test3',
    ]);
    return (new \MagicProControllers\test3)->handle($request, $any);
})->where('any', '.*');
/*stop11*//*start11*/


Route::get('/test3{any?}/', function (\Illuminate\Http\Request $request, $any = null) {
    $request->attributes->add([
        'name' => 'test3',
        'title' => 'Тестирование 3',
        'artId' => 11,
        'parentId' => 1,
        'view'  => 'magic::test3',
    ]);
    return (new \MagicProControllers\test3)->handle($request, $any);
})->where('any', '.*');
/*stop11*//*start19*/


Route::get('/components{any?}/', function (\Illuminate\Http\Request $request, $any = null) {
    $request->attributes->add([
        'name' => 'components',
        'title' => 'Компоненты',
        'artId' => 19,
        'parentId' => 1,
        'view'  => 'magic::components',
    ]);
    return (new \MagicProControllers\components)->handle($request, $any);
})->where('any', '.*');
/*stop19*//*start30*/
Route::get('/tLiveVare{any?}/', function (\Illuminate\Http\Request $request, $any = null) {
    $request->attributes->add([
        'name' => 'tLiveVare',
        'title' => 'Тестируем лайвВаре',
        'artId' => 30,
        'parentId' => 1,
        'view'  => 'magic::tLiveVare',
    ]);
    return (new \MagicProControllers\tLiveVare)->handle($request, $any);
})->where('any', '.*');
/*stop30*//*start27*/
Route::get('/', function (\Illuminate\Http\Request $request, $any = null) {
    $request->attributes->add([
        'name' => 'index',
        'title' => 'Заглавная',
        'artId' => 27,
        'parentId' => 1,
        'view'  => 'magic::index',
    ]);
    return (new \MagicProControllers\index)->handle($request, $any);
})->where('any', '.*');
/*stop27*/