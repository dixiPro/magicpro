<?php

namespace MagicProSrc;
// общий контроллер для статей

// use App\Http\Controllers\Controller;
use Illuminate\View\Component;

abstract class MagicController // extends Component
{

    // public function __construct(?string $nameArt = null)
    // {
    //     $a = 1;
    //     $this->nameArt = $nameArt; // ключевой момент

    // }

    // public function render() // : \Illuminate\View\View
    // {
    //     $b = 1;
    //     return view('magic::' . class_basename(static::class), [
    //         // любые данные
    //     ]);
    // }

    // handle для роутов
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
