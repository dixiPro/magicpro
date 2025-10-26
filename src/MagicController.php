<?php

namespace MagicProSrc;
// общий контроллер для статей

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

abstract class MagicController extends Controller
{
    // Универсальный метод handle для роутов
    public function handle(...$args)
    {
        [$request, $getParams] = $args;

        // $attr = $request->attributes->all();

        $view = $request->attributes->get('view');

        view()->share('Env', $request->attributes->all());
        view()->share('Get', $getParams);

        $data = $this->process($request, $getParams);

        return view($view, $data);
    }

    // Обязателен в наследниках — возвращает данные для вьюхи
    //  а тут прут ошибки
    abstract protected function process(...$args): array;
}
