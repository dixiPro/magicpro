<?php

namespace MagicProSrc\Routing;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use MagicProDatabaseModels\Article;
use MagicProSrc\Config\MagicGlobals;

class DynamicRouteHandler
{
    // ---------- Состояние запроса ----------

    private Request $request;
    private array   $segments;     // сегменты URL без первого
    private string  $articleName;  // нормализованное имя статьи первый сегмент или индекс
    private array   $article;      // данные статьи из БД
    private array   $input  = [];    // входные параметры
    private string  $method  = 'GET';    // или POST заглавными
    private array   $params  = [];    // итоговые параметры после чекера, для конролера или вьюхи

    // ---------- Оркестратор ----------

    public function handle(Request $request)
    {
        try {
            $this->init($request);
            $this->findArticle();
            $this->check();
            return $this->build();
        } catch (\Throwable $e) {
            return $this->onError($e);
        }
    }

    // ---------- Шаг 1: инициализация ----------

    private function init(Request $request): void
    {
        $this->request = $request;
        $this->post    = $request->post();

        $segments = $request->segments();
        if (empty($segments)) {
            $page = 'index';
        } else {
            $page = $segments[0];
            array_shift($segments);
        }

        $this->articleName = $this->normalizePageName($page);
        $this->segments    = $segments;
    }

    private function normalizePageName(string $page): string
    {
        // Своп точки и ___ (оригинальная логика, разбираемся отдельно)
        $pos = strpos($page, '.');
        if ($pos === false) {
            $page = str_replace('___', '.', $page);
        } else {
            $page = str_replace('.', '___', $page);
        }

        $page = trim($page);

        if (!preg_match('/^[A-Za-z0-9_-]+$/', $page)) {
            throw new \Exception('невалидная статья');
        }

        return $page;
    }

    // ---------- Шаг 2: поиск статьи ----------

    private function findArticle(): void
    {
        $article = Article::where('name', $this->articleName)->first();
        if (!$article) {
            throw new \Exception('статья не найдена');
        }

        $this->article = $article->toArray();

        if (empty($this->article['isRoute'])) {
            throw new \Exception('route not enabled');
        }

        // Доступ только админам — намеренно 404, чтобы скрыть существование
        $routeParams = $this->article['routeParams'];
        if (!empty($routeParams['adminOnly']) && !Auth::guard('magic')->check()) {
            throw new \Exception('Только админам');
        }
    }

    // ---------- Шаг 3: проверка параметров маршрута ----------

    private function check(): void
    {
        $routeParams = $this->article['routeParams'];

        $segmentParams = $this->parseSegments($routeParams);
        $allQuery      = array_merge($segmentParams, $this->request->query());
        $remainingKeys = array_keys($allQuery);

        // Убираем разрешённые UTM
        if (!empty($routeParams['utmParamsEnable'])) {
            $remainingKeys = array_diff($remainingKeys, MagicGlobals::$INI['ENABLE_URL_PARAMS']);
        }

        // GET запрещён — после вычистки UTM ничего не должно остаться
        if (empty($routeParams['getEnable'])) {
            if (empty($remainingKeys)) {
                $this->get = $allQuery;
                return;
            }
            throw new \Exception('Гет запрещено, но параметры есть');
        }

        // GET разрешён, привязанных параметров нет — пропускаем что угодно
        if (empty($routeParams['keysArr'])) {
            $this->get = $allQuery;
            return;
        }

        // GET разрешён, есть привязка — убираем привязанные, проверяем остаток
        $remainingKeys = array_diff($remainingKeys, $routeParams['keysArr']);
        if (empty($remainingKeys)) {
            $this->get = $allQuery;
            return;
        }

        throw new \Exception('Неразрешённые параметры в запросе');
    }

    private function parseSegments(array $routeParams): array
    {
        $segments = $this->segments;
        $keysArr  = $routeParams['keysArr'] ?? [];

        $bindKeys = !empty($keysArr)
            && !empty($routeParams['bindKeys'])
            && !empty($routeParams['getEnable']);

        if ($bindKeys) {
            if (count($segments) !== count($keysArr)) {
                throw new \Exception('количество ключей не совпадает');
            }
            return array_combine($keysArr, $segments);
        }

        // Непривязанные параметры: пары /key1/val1/key2/val2
        $keys   = array_values(array_filter($segments, fn($v, $i) => $i % 2 == 0, ARRAY_FILTER_USE_BOTH));
        $values = array_values(array_filter($segments, fn($v, $i) => $i % 2 == 1, ARRAY_FILTER_USE_BOTH));

        if (count($keys) !== count($values)) {
            throw new \Exception('параметры segment нечетные');
        }

        return array_combine($keys, $values);
    }

    // ---------- Шаг 4: сборка ответа ----------

    private function build()
    {
        $env = [
            'name'     => $this->article['name'],
            'title'    => $this->article['title'],
            'artId'    => $this->article['id'],
            'parentId' => $this->article['parentId'],
            'view'     => 'magic::' . $this->article['name'],
        ];

        $routeParams = $this->article['routeParams'];

        // Без контроллера — отдаём view напрямую
        $useController = !array_key_exists('useController', $routeParams)
            || $routeParams['useController'];

        if (!$useController) {
            return view($env['view'], [
                'Env'  => $env,
                'Get'  => $this->get,
                'Post' => $this->post,
            ]);
        }

        // С контроллером
        $controllerName = '\\MagicProControllers\\' . $this->article['name'];
        $this->request->attributes->add($env);
        $controller = new $controllerName();
        return $controller->handle($this->request, $this->get, $this->post);
    }

    // ---------- Шаг 5: обработка ошибок ----------

    private function onError(\Throwable $e)
    {
        Log::warning('Route 404', [
            'message' => $e->getMessage(),
            'url'     => $this->request->fullUrl(),
        ]);

        return response()->view('magic::' . ART_NAME_404, [
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
        ], 404);
    }
}
