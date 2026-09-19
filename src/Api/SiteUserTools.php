<?php

namespace MagicProSrc\Api;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * What API_SiteAuth and API_Users both do with a user of the site: the same
 * rules for an email and a password, the same limit on wrong passwords.
 */
trait SiteUserTools
{
    /** Wrong passwords in a row before the pause, and the pause in seconds. */
    protected const LOGIN_ATTEMPTS = 5;
    protected const LOGIN_DECAY = 60;

    /** A valid email in lower case, or null. The domain is checked by DNS. */
    protected static function cleanEmail(mixed $email): ?string
    {
        $email = mb_strtolower(trim(is_string($email) ? $email : ''));

        $validator = Validator::make(
            ['email' => $email],
            ['email' => ['required', 'email:rfc,dns', 'max:255']]
        );

        return $validator->fails() ? null : $email;
    }

    /** A password of 8 to 255 characters without edge spaces, or null. */
    protected static function cleanPassword(mixed $password): ?string
    {
        $password = trim(is_string($password) ? $password : '');

        $validator = Validator::make(
            ['password' => $password],
            ['password' => ['required', 'string', 'min:8', 'max:255']]
        );

        return $validator->fails() ? null : $password;
    }

    /**
     * Sign in by email and password.
     *
     * Returns '' on success, otherwise `too_many_attempts` or `wrong_password`.
     * The limit counts per email and address, so one bot cannot lock a person
     * out from everywhere.
     */
    protected static function attemptLogin(string $email, string $password, bool $remember = true): string
    {
        $key = Str::transliterate('login:' . $email . '|' . request()->ip());

        if (RateLimiter::tooManyAttempts($key, self::LOGIN_ATTEMPTS)) {
            return 'too_many_attempts';
        }

        if ($password === '' || !Auth::attempt(['email' => $email, 'password' => $password], $remember)) {
            RateLimiter::hit($key, self::LOGIN_DECAY);

            return 'wrong_password';
        }

        RateLimiter::clear($key);
        self::regenerateSession();

        return '';
    }

    /** Sign in a user already checked by other means: a link from a letter. */
    protected static function loginUser(User $user, bool $remember = true): void
    {
        Auth::login($user, $remember);
        self::regenerateSession();
    }

    /** A new session id after signing in: the old one could be planted. */
    protected static function regenerateSession(): void
    {
        if (request()->hasSession()) {
            request()->session()->regenerate();
        }
    }
}
