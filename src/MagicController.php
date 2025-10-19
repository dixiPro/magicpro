<?php

namespace MagicProSrc;
// общий контроллер для статей

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

abstract class MagicController extends Controller
{
    // Универсальный метод handle для роутов
    public function handle(Request $request)
    {
        $attr = $request->attributes->all();
        $view = $request->attributes->get('view');

        view()->share('Env', $request->attributes->all());

        $data = $this->process($request);

        return view($view, $data);
    }

    // Обязателен в наследниках — возвращает данные для вьюхи
    abstract protected function process(Request $request): array;
}
