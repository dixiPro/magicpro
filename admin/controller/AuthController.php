<?php

namespace MagicProAdminControllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use MagicProDatabaseModels\MagicProUser;

class AuthController
{
    public function login(Request $request)
    {
        $user = MagicProUser::where('email', $request->email)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            // Auth::login($user, $request->boolean('remember'));
            Auth::guard('magic')->login($user, $request->boolean('remember'));
            // возвращаем на ту же страницу
            return redirect()->back();
        }

        return redirect()->back()->with('mpro_error', 'Неверный логин или пароль');
    }

    public function logout()
    {
        // Auth::logout();
        Auth::guard('magic')->logout();
        return redirect()->back();
    }
}
