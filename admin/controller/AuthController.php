<?php

namespace MagicProAdminControllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use MagicProDatabaseModels\MagicProUser;

class AuthController
{
    /** Wrong passwords in a row for one email from one address, then a pause. */
    private const ATTEMPTS = 5;

    private const PAUSE = 60;

    /**
     * Sign in to the admin panel.
     *
     * Wrong passwords are counted per email and address: five in a row and the
     * pair waits a minute. Without it a password of an admin could be tried
     * as fast as the server answers.
     *
     * After a good sign-in the session gets a new id: one that somebody
     * planted or saw before the sign-in is worth nothing afterwards.
     *
     * An empty password is refused before anything else. Admins added from
     * the screen used to get the hash of an empty string, and such a hash
     * accepts an empty field.
     */
    public function login(Request $request)
    {
        $email    = mb_strtolower(trim((string) $request->input('email', '')));
        $password = (string) $request->input('password', '');
        $key      = 'magic-login:' . $email . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, self::ATTEMPTS)) {
            return redirect()->back()->with(
                'mpro_error',
                'too many attempts, try again in ' . RateLimiter::availableIn($key) . ' s'
            );
        }

        $user = $email !== '' ? MagicProUser::where('email', $email)->first() : null;

        if ($user && $password !== '' && Hash::check($password, $user->password)) {
            RateLimiter::clear($key);

            Auth::guard('magic')->login($user, $request->boolean('remember'));

            $request->session()->regenerate();

            // redirect back to the same page
            return redirect()->back();
        }

        RateLimiter::hit($key, self::PAUSE);

        return redirect()->back()->with('mpro_error', 'invalid login or password');
    }

    /**
     * Sign out, by POST with the form token.
     *
     * It used to be a GET link, and any page on the web could sign an admin
     * out with an <img> pointing at it.
     */
    public function logout()
    {
        Auth::guard('magic')->logout();

        return redirect()->back();
    }
}
