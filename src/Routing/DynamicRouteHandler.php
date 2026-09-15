<?php

namespace MagicProSrc\Routing;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\DB;
use MagicProDatabaseModels\Article;
use Illuminate\Support\Facades\Auth;
use MagicProSrc\Config\MagicGlobals; // Глобальные константы


class DynamicRouteHandler
{

    private function checkRout(Request $request, $routeParams, $segments)
    {
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
            if (count($segments) !== count($routeParams['keysArr'])) {
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

        // все параметры в виде ключ-значение.
        //
        // Путь сильнее query string. Раньше было наоборот, и при привязанных
        // ключах `/product/expected?slug=replaced` отдавал контроллеру
        // `slug = replaced`: адрес говорил одно, а код получал другое, и
        // полагаться на позиционный параметр было нельзя. Теперь ключ из пути
        // query string не перебивает
        $allQuery = array_merge($request->query(), $segmentParams);
        // если все проверки пройдут ок этот массив вернется в виде результата

        // все ключи
        $allQueryKeys = array_keys($allQuery);

        // дальше работаем только с ключами


        // Оставшиеся ключи
        $remainingKeys = $allQueryKeys;


        // Удаляем утм ключи, если УТМ   разрешен
        if ($routeParams["utmParamsEnable"]) {
            $remainingKeys = array_diff($remainingKeys, MagicGlobals::$INI['ENABLE_URL_PARAMS']);
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

    /**
     * Метод запроса против того, что статья умеет.
     *
     * Динамический маршрут зарегистрирован через `Route::any()`, иначе адреса
     * статей пришлось бы объявлять по одному. Плата за это — в статью приходит
     * что угодно: `PUT`, `DELETE`, `PROPFIND`. Раньше всё это открывало
     * страницу как обычный `GET`, а `postEnable` только выбирал, откуда читать
     * параметры.
     *
     * Правило простое: страницу отдаём на `GET` и `HEAD`, `POST` принимаем
     * только там, где он разрешён статьёй, остальное — 405.
     */
    private function checkMethod(Request $request, bool $postEnable): void
    {
        $method = $request->method();

        if ($method === 'GET' || $method === 'HEAD') {
            return;
        }

        if ($method === 'POST' && $postEnable) {
            return;
        }

        abort(405, $method === 'POST'
            ? 'this article does not accept POST: turn postEnable on'
            : 'method not allowed');
    }

    /**
     * Токен формы, если форма его прислала.
     *
     * Динамический маршрут выведен из-под csrf-middleware целиком: иначе любая
     * страница сайта с формой без `@csrf` перестала бы работать после
     * обновления пакета. Но `@csrf` в блейде до сих пор рисовал поле, которое
     * никто не проверял, — а это хуже, чем отсутствие защиты: автор формы
     * уверен, что защищён.
     *
     * Поэтому середина: прислали токен — он обязан быть верным. Форма с
     * `@csrf` получает настоящую проверку, старые формы работают как работали.
     */
    private function checkToken(Request $request): void
    {
        $token = $request->input('_token') ?: $request->header('X-CSRF-TOKEN');

        if ($token === null || $token === '') {
            return;
        }

        if (! is_string($token) || ! hash_equals((string) $request->session()->token(), $token)) {
            abort(419, 'csrf token mismatch');
        }
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
            throw new \Exception('route not eneble');
        }

        // every key in place: an article saved before the defaults were one
        // set may lack some, and a missing key used to end in 404
        $routeParams = Article::routeParams($article['routeParams'] ?? null);

        // только для админа
        if ($routeParams['adminOnly'] && ! Auth::guard('magic')->check()) {
            throw new \Exception('Только админам');
        }

        $this->checkMethod($request, $routeParams['postEnable']);

        $postParams = [];
        $res = [];

        // проверка поста
        if ($routeParams['postEnable']) {
            $this->checkToken($request);

            $postParams = $request->post();
        } else {
            $res = $this->checkRout($request, $routeParams, $segments);
        }

        // Тут все верно
        $name      = $article['name'];
        $title     = $article['title'];
        $artId     = $article['id'];
        $parentId  = $article['parentId'];
        $isRoute  = $article['isRoute'];
        $view      = 'magic::' . $article['name'];
        $controllerName = '\\MagicProControllers\\' . $name;

        $env = compact('name', 'title', 'artId', 'parentId', 'view');

        // без контроллера — сразу вьюха
        if (! $routeParams['useController']) {
            return view($view, [
                'Env' => $env,
                'Get' => $res,
                'Post' => $postParams
            ]);
        }

        // добавляем атрибуты
        $request->attributes->add($env);
        $controller = new $controllerName();
        return $controller->handle([
            'request' => $request,
            'getParams' => $res ?? [],
            'postParams' => $postParams ?? [],
        ]);
    }

    public function handle(Request $request)
    {
        try {
            $controller = $this->checkFirts($request);
            return $controller;
        } catch (HttpException $e) {
            // 405 и 419 отвечают за себя сами: подменять их страницей «не
            // найдено» — врать. Адрес есть, беда в методе или в токене
            throw $e;
        } catch (\Throwable $th) {
            return response()->view('magic::' . ART_NAME_404, [
                'message' => $th->getMessage(),
                'file'    => $th->getFile(),
                'line'    => $th->getLine(),
            ], 404);
        }
    }
}
