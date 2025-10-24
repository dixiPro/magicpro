<?php

namespace MagicProSrc\Routing;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use MagicProDatabaseModels\Article;


class DynamicRouteHandler
{
    public function handle(Request $request, $any = null)
    {
        // 🔹 Получаем текущий путь
        $segments = $request->segments(); // ['testPage', 'param1', 'param2']  
        // заглавная
        if (empty($segments)) {
            $page = 'index';
        } else {
            $page = $segments[0];
            array_shift($segments);
        }

        // 🔹 Ищем запись в базе
        $article = Article::where('name', $page)->first()->toArray();

        // 🔹 Если запись не найдена — 404
        if (empty($article)) {
            abort(404);
        }

        // Тут все верно
        $name      = $article['name'];
        $title     = $article['title'];
        $artId     = $article['id'];
        $parentId  = $article['parentId'] ?? null;
        $isRoute  = $article['isRoute'] ?? null;
        $view      = 'magic::' . $article['name'];
        $controllerName = '\\MagicProControllers\\' . $name;

        $routeParams = $article['routeParams'];

        if (!$isRoute) {
            abort(404);
            return null;
        }

        // параметры запрещены
        if (!$routeParams['paramsEnable']) {
            // есть еще в сегменте 
            if (!empty($segments)) {
                abort(404);
                return null;
            }
            // если есть не разрешенные квери 404
            $queryKeys = array_keys(request()->query());
            $matched = array_diff($queryKeys, ENABLE_URL_PARAMS);
            if (!empty($matched)) {
                abort(404);
                return null;
            }
        }

        // 
        // $data = $request->all();для дебага
        // 
        $request->attributes->add(compact('name', 'title', 'artId', 'parentId', 'view'));
        // 
        // $data = $request->attributes->all(); для дебага
        // 

        $controller = new $controllerName();

        $segments = $request->segments(); // ['testPage', 'param1', 'param2']
        $query  = $request->query();

        // управление передается правильно
        return $controller->handle($request, $any);
    }
}
