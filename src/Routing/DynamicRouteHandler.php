<?php

namespace MagicProSrc\Routing;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DynamicRouteHandler
{
    public function handle(Request $request, $any = null)
    {
        // 🔹 Получаем текущий путь
        $path = trim($request->path(), '/');

        // заглавная
        $articleName = $path === '' ? 'index' : $path;

        // 🔹 Для отладки
        $debug_path = $path;

        // 🔹 Ищем запись в базе
        $row = DB::table('articles')->where('name', $articleName)->first();

        // 🔹 Если запись не найдена — 404
        if (!$row) {
            abort(404);
        }

        // Тут все верно
        $name      = $row->name;
        $title     = $row->title;
        $artId     = $row->id;
        $parentId  = $row->parentId ?? null;
        $isRoute  = $row->isRoute ?? null;
        $view      = 'magic::' . ($row->view ?? $row->name ?? 'default');
        $controllerName = '\\MagicProControllers\\' . $name;

        if (!$isRoute) {
            abort(404);
        }

        // 
        $data = $request->all();
        $request->attributes->add(compact('name', 'title', 'artId', 'parentId', 'view'));
        $data = $request->attributes->all();
        $controller = new $controllerName();

        // управление передается правильно
        return $controller->handle($request, $any);
    }
}
