<?php

namespace MagicProSrc\Routing;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use MagicProDatabaseModels\Article;
use Illuminate\Support\Facades\Auth;


class DynamicRouteHandler
{

    private function checkRout(Request $request, $routeParams, $segments)
    {
        // только для админа
        if ($routeParams['adminOnly'] && ! Auth::guard('magic')->check()) {
            throw new \Exception('Только админам');
        }
        // 
        // парсим все данные в один массив = сегмент + квери
        // выбрасываем разрешенные
        // если ничего не осталось, значит запрос валиден
        // 

        // достаем все параметры из сегмента
        $segmentParams = []; // параметры из сегмента

        // есть ли привязанные параметры
        $bindKeys = !empty($routeParams['keysArr']) && $routeParams['bindKeys'] && $routeParams['getEnable'];

        if ($bindKeys) {
            // ключи в $routeParams['keysArr']
            // значения в   $segments

            // количество ключей не совпадает
            if (count($segmentParams) !== count($routeParams['keysArr'])) {
                throw new \Exception('количество ключей не совпадает');
            }
            // ключи из $routeParams['keysArr'] значения из  $segments
            $segmentParams = array_combine($routeParams['keysArr'], $segments);
            // проверка
            $valid = $segmentParams && !in_array(null, $segmentParams, true);
            if (!$valid) {
                throw new \Exception('неверные данные в ключе');
            }
        } else {
            // параметны  не привязаны к сегменту
            // ключи в один массив
            $keys = array_values(array_filter($segments, fn($v, $i) => $i % 2 == 0, ARRAY_FILTER_USE_BOTH));
            // значения в другой
            $values = array_values(array_filter($segments, fn($v, $i) => $i % 2 == 1, ARRAY_FILTER_USE_BOTH));
            // 
            if (count($keys) !== count($values)) {
                throw new \Exception('параметры segment нечетные');
            }
            $segmentParams = array_combine($keys, $values);
        }

        // все параметры в  виде ключ-значение
        $allQuery = array_merge($segmentParams, $request->query());
        // если все проверки пройдут ок этот массив вернется в виде результата

        // все ключи
        $allQueryKeys = array_keys($allQuery);

        // дальше работаем только с ключами


        // Оставшиеся ключи
        $remainingKeys = $allQueryKeys;


        // Удаляем утм ключи, если УТМ   разрешен
        if ($routeParams["utmParamsEnable"]) {
            $remainingKeys = array_diff($remainingKeys, ENABLE_URL_PARAMS);
        }

        // гет запрещены 
        // 
        if (!$routeParams['getEnable']) {
            // УТМ мы уже вычистили если они разрешены
            // поэтому в параметрах должно быть пусто
            if (empty($remainingKeys)) {
                return  $allQuery;
            } else {
                throw new \Exception('Гет запрещено, но параметры есть');
            }
        }
        // гет параметры разрешены
        // нет привязанных параметров
        if (empty($routeParams['keysArr'])) {
            return $allQuery;
        }

        // есть привязанные параметры
        // Удаляем привязаные параметры 
        $remainingKeys = array_diff($remainingKeys, $routeParams['keysArr']);

        // в ключах ничего нет
        // запрос ок
        if (empty($remainingKeys)) {
            return $allQuery;
        }
        throw new \Exception('Ошибка в checkRout');
    }

    private function checkFirts(Request $request)
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
        // обработка раута с точкой — файлы якобы
        $pos = strpos($page, '.');
        if ($pos === false) {
            $page = str_replace("___", '.',  $page); // заменяем ___ на точку, что бы не было вызов статйей с ___
        } else {
            $page = str_replace('.', "___", $page);
        }


        // 🔹 Ищем запись в базе
        $page = trim($page);
        // могут быть только буквы, цифры и - _
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $page)) {
            throw new \Exception('невалидная статья');
        }
        $article = Article::where('name', $page)->first();
        if (!$article) {
            throw new \Exception('статья не найдена');
        }
        $article = $article->toArray();

        // нет раута
        if (! ($article['isRoute'] ?? null)) {
            throw new \Exception('раут запрещен');
        }

        $routeParams = $article['routeParams'];

        $res = $this->checkRout($request, $article['routeParams'], $segments);

        // Тут все верно
        $name      = $article['name'];
        $title     = $article['title'];
        $artId     = $article['id'];
        $parentId  = $article['parentId'];
        $isRoute  = $article['isRoute'];
        $view      = 'magic::' . $article['name'];
        $controllerName = '\\MagicProControllers\\' . $name;

        $env = compact('name', 'title', 'artId', 'parentId', 'view');

        // ключ есть и равен false
        if (array_key_exists('useController', $article['routeParams']) &&  !$article['routeParams']['useController']) {
            return view($view, [
                'Env' => $env,
                'Get' => $res
            ]);
        }

        // добавляем атрибуты
        $request->attributes->add($env);
        $controller = new $controllerName();
        return $controller->handle($request,  $res);
    }

    public function handle(Request $request)
    {
        try {
            $controller = $this->checkFirts($request);
            return $controller;
        } catch (\Throwable $th) {
            return response()->view('magic::' . ART_NAME_404, [
                'message' => $th->getMessage(),
                'file'    => $th->getFile(),
                'line'    => $th->getLine(),
            ], 404);
        }
    }
}
