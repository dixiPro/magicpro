<?php

namespace MagicProAdminMiddleware;

use MagicProSrc\MagicLang;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckMagicAuth
{
    public function handle($request, Closure $next, $role = null)
    {
        // MagicLang::loadLocale('ru');

        $guard = Auth::guard('magic');

        if (!$guard->check()) {
            return response()->json([
                'status' => 'error',
                'msg' => 'MagicPro authorization required'
            ], 401);
        }

        $user = $guard->user();
        $roles = ['admin', 'editor'];
        if (!in_array($user->role, $roles)) {
            return response()->json([
                'status' => 'error',
                'msg' => 'Insufficient rights (requires role: ' . $role . ')'
            ], 403);
        }

        return $next($request);
    }
}
