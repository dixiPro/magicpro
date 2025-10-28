<?php

namespace MagicProSrc;

// общий контроллер для статей

use Illuminate\View\Component;

abstract class MagicController
{
    // handle для роутов
    public function handle(...$args)
    {
        [$request, $getParams] = $args;

        // атрибуты из раута, статья id и другие параметры
        $view = $request->attributes->get('view');

        view()->share('Env', $request->attributes->all());
        view()->share('Get', $getParams);

        // вызываем наследников
        $data = $this->process($request, $getParams);

        return view($view, $data);
    }

    // Обязателен в наследниках — возвращает данные для вьюхи

    abstract protected function process(...$args): array;
}
