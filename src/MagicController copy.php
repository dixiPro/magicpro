<?php

namespace MagicProSrc;

// общий контроллер для статей

use Illuminate\View\Component;

abstract class MagicController
{
    protected string $contentType = 'text/html; charset=utf-8';
    protected int $status = 200;
    protected array $headers = [];
    protected ?string $redirect = null;

    public function handle(...$args)
    {
        try {
            [$request, $getParams, $postParams] = $args;

            $view = $request->attributes->get('view');

            view()->share('Env', $request->attributes->all());
            view()->share('Get', $getParams);

            $data = $this->process($request, $getParams, $postParams);

            if ($this->redirect) {
                return redirect($this->redirect);
            }

            return response(
                view($view, $data)->render(),
                $this->status,
                array_merge(
                    ['Content-Type' => $this->contentType],
                    $this->headers
                )
            );
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    protected function errorResponse(\Throwable $e)
    {
        $out  = '<pre>';
        $out .= 'error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'utf-8') . "\n\n";

        if (config('app.debug')) {
            $out .= htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'utf-8');
        }

        $out .= '</pre>';

        return response($out, 500, [
            'Content-Type' => 'text/html; charset=utf-8',
        ]);
    }

    abstract protected function process(...$args): array;
}
