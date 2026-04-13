<?php

namespace MagicProSrc;

// общий контроллер для статей

use Illuminate\View\Component;

abstract class MagicController
{
    // handle для роутов
    public function handle(...$args)
    {
        try {
            [$request, $getParams, $postParams] = $args;

            // атрибуты из раута, статья id и другие параметры
            $view = $request->attributes->get('view');

            view()->share('Env', $request->attributes->all());
            view()->share('Get', $getParams);

            // вызываем наследников
            $data = $this->process($request, $getParams, $postParams);
            if (($data['redirect'] ?? false)) {
                return  redirect($data['redirect']);
            }

            if (($data['ContentType'] ?? 'unknown') !== 'unknown') {
                return response(
                    view($view, $data)->render(),
                    200,
                    ['Content-Type' => $data['ContentType']]
                );
            } else {
                return view($view, $data);
            }
        } catch (\Throwable $e) {

            $out  = "<pre>";
            $out .= "error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'utf-8') . "\n\n";

            if (config('app.debug')) {
                $out .= $e->getTraceAsString();
            }

            $out .= "</pre>";

            return response($out, 500, [
                'content-type' => 'text/html; charset=utf-8',
            ]);

            // return response()->view('magic::error', [
            //     'error' => $e->getMessage(),
            //     'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            //     'view' => $view
            // ], 500);
            //throw $th;
        }
    }

    // Обязателен в наследниках — возвращает данные для вьюхи

    abstract protected function process(...$args): array;
}
