<?php

namespace MagicProSrc;

use App\Http\Controllers\Controller;
use Illuminate\View\Component;
use Illuminate\Http\Request;

abstract class MagicController extends Controller
{
    // ✅ общий метод для обоих режимов
    protected function renderView(Request $request = null, array $getParams = [])
    {
        $view = $request?->attributes->get('view') ?? $this->view ?? 'magic::default';

        view()->share('Env', $request?->attributes->all() ?? []);
        view()->share('Get', $getParams);

        $data = $this->process($request, $getParams);
        return view($view, $data);
    }

    // ✅ как контроллер
    public function handle(Request $request, array $getParams = [])
    {
        return $this->renderView($request, $getParams);
    }

    // ✅ как компонент
    public function render()
    {
        return $this->renderView();
    }

    abstract protected function process(?Request $request = null, array $getParams = []): array;
}
