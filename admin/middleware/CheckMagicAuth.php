<?php

namespace MagicProAdminMiddleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckMagicAuth
{
    public function handle($request, Closure $next, $role = null)
    {
        $guard = Auth::guard('magic');

        if (!$guard->check()) {
            return response()->json([
                'status' => 'error',
                'msg' => 'Требуется авторизация MagicPro'
            ], 401);
        }

        $user = $guard->user();

        if ($role && $user->role !== $role) {
            return response()->json([
                'status' => 'error',
                'msg' => 'Недостаточно прав (нужна роль: ' . $role . ')'
            ], 403);
        }

        return $next($request);
    }
}
