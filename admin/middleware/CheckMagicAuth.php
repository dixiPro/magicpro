<?php

namespace MagicProAdminMiddleware;

use MagicProSrc\MagicLang;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckMagicAuth
{
    public function handle($request, Closure $next, ...$roles)
    {
        $guard = Auth::guard('magic');

        if (!$guard->check()) {
            return response()->json([
                'status' => 'error',
                'msg' => 'MagicPro authorization required',
            ], 401);
        }

        $roles = $roles ?: ['admin'];

        if (!in_array($guard->user()->role, $roles, true)) {
            return response()->json([
                'status' => 'error',
                'msg' => 'Insufficient rights',
            ], 403);
        }

        return $next($request);
    }
}
