<?php

namespace MagicProSrc\Api;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;

class API_Auth extends AbstractApi
{
    protected array $map = [
        'authEmailPassword' => 'authEmailPassword',
        'createUser' => 'createUser',
        'deleteUser' => 'deleteUser',
        'logout' => 'logout',
        'currentUser' => 'currentUser',
        'cryptEmailPass' => 'cryptEmailPass',
        'decryptEmailPass' => 'decryptEmailPass',
        'userInfo' => 'userInfo',
        'processNewUser' => 'processNewUser'
    ];

    // ==================================================================
    //                     helper methods
    // ==================================================================
    protected function processNewUser(array $params): array
    {
        $key = (string) ($params['key'] ?? '');

        // Расшифровать входные параметры
        $res = self::run('decryptEmailPass', [
            'key' => $key,
        ]);

        if (!$res['status']) {
            throw new ApiException(
                $res['errorMsg'] ?: 'Decrypt error',
                ['situation' => 'decrypt_error']
            );
        }

        $data = $res['data'];

        // Пользователь уже авторизован
        if (Auth::check()) {
            return [
                'back' => $data['back'] ?? '/',
                'situation' => 'user_auth',
            ];
        }

        // Проверить, зарегистрирован ли пользователь
        $userInfo = self::run('userInfo', [
            'email' => $data['email'],
        ]);

        // Пользователь зарегистрирован — пробуем авторизовать
        if ($userInfo['status']) {
            $resAuth = self::run('authEmailPassword', [
                'email' => $data['email'],
                'password' => $data['password'],
                'ip' => request()->ip(),
            ]);

            if (!$resAuth['status']) {
                throw new ApiException(
                    $resAuth['errorMsg'] ?: 'Invalid password',
                    ['situation' => 'user_auth_incorrect_passw']
                );
            }

            return [
                'back' => $data['back'] ?? '/',
                'situation' => 'user_auth',
            ];
        }

        // Зарегистрировать пользователя
        $resCreate = self::run('createUser', [
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        if (!$resCreate['status']) {
            throw new ApiException(
                $resCreate['errorMsg'] ?: 'Registration error',
                ['situation' => 'user_registration_error']
            );
        }

        // Авторизовать нового пользователя
        $resAuth = self::run('authEmailPassword', [
            'email' => $data['email'],
            'password' => $data['password'],
            'ip' => request()->ip(),
        ]);

        if (!$resAuth['status']) {
            throw new ApiException(
                $resAuth['errorMsg'] ?: 'Authorization error',
                ['situation' => 'user_auth_incorrect_passw']
            );
        }

        return [
            'back' => $data['back'] ?? '/',
            'situation' => 'user_registered',
        ];
    }

    protected function userInfo(array $params): array
    {
        $email = $this->validateEmail($params);

        $user = User::where('email', $email)
            ->first(['name', 'email', 'created_at']);

        if (!$user) {
            throw new \Exception('User not found');
        }

        return [
            'email' => $user->email,
            'name' => $user->name,
            'created_at' => $user->created_at,
        ];
    }

    protected function validateEmail(array $params): string
    {
        $email = mb_strtolower(
            trim((string) ($params['email'] ?? ''))
        );

        $validator = Validator::make(
            ['email' => $email],
            ['email' => ['required', 'string', 'email', 'max:255']]
        );

        if ($validator->fails()) {
            throw new \Exception('Invalid email');
        }

        return $email;
    }

    protected function validatePassword(array $params): string
    {
        $password = trim((string) ($params['password'] ?? ''));

        $validator = Validator::make(
            ['password' => $password],
            ['password' => ['required', 'string', 'min:8', 'max:255']]
        );

        if ($validator->fails()) {
            throw new \Exception('Password must be at least 8 characters');
        }
        return $password;
    }


    protected function cryptEmailPass(array $params): array
    {
        $data = [
            'email' => $this->validateEmail($params),
            'password' => $this->validatePassword($params),
            'date' => now()->timestamp,
            'back' => trim((string) ($params['back'] ?? '')) ?: '/',
        ];

        $encrypted = Crypt::encryptString(
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
        );

        $key = rtrim(
            strtr(base64_encode($encrypted), '+/', '-_'),
            '='
        );

        return [
            'key' => $key,
            'data' => $data
        ];
    }

    protected function decryptEmailPass(array $params): array
    {
        $key = (string) ($params['key'] ?? '');
        $hours = (int) ($params['hours'] ?? 24);
        try {
            $key .= str_repeat('=', (4 - strlen($key) % 4) % 4);

            $encrypted = base64_decode(
                strtr($key, '-_', '+/'),
                true
            );

            if ($encrypted === false) {
                throw new \Exception();
            }

            $data = json_decode(
                Crypt::decryptString($encrypted),
                true,
                flags: JSON_THROW_ON_ERROR
            );

            if (
                !is_array($data) ||
                !isset($data['email'], $data['password'], $data['date'])
            ) {
                throw new \Exception();
            }

            if (now()->timestamp - (int) $data['date'] > $hours * 60 * 60) {
                throw new \Exception('Key expired');
            }
        } catch (\Throwable $e) {
            throw new \Exception('Key decryption failed', previous: $e);
        }

        return $data;
    }

    protected function logout(array $params): array
    {
        Auth::logout();

        return [];
    }

    protected function authEmailPassword(array $params): array
    {
        $email = $this->validateEmail($params);
        $password = $this->validatePassword($params);
        $remember = (bool) ($params['remember'] ?? true);

        $ip = (string) ($params['ip'] ?? request()->ip());

        $key = Str::transliterate(
            'login:' . $email . '|' . $ip
        );
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw new \Exception("too many attempts, try again in {$seconds} seconds");
        }

        if (!Auth::attempt([
            'email' => $email,
            'password' => $password,
        ], $remember)) {
            RateLimiter::hit($key, 60);
            throw new \Exception('invalid email or password');
        }

        RateLimiter::clear($key);

        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        $user = Auth::user();

        return [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,

        ];
    }

    protected function currentUser(array $params): array
    {
        $user = Auth::user();

        if (!$user) {
            return ['auth' => false];
        }

        return [
            'auth' => true,
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
        ];
    }

    protected function createUser(array $params): array
    {
        $email = $this->validateEmail($params);
        $password = $this->validatePassword($params);
        $name = trim((string) ($params['name'] ?? ''));

        if (User::where('email', $email)->exists()) {
            throw new \Exception("user with email {$email} already exists");
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make($password),
        ]);

        return [
            'id' => $user->id,
            'email' => $user->email,
        ];
    }

    protected function deleteUser(array $params): array
    {
        $email = (string) ($params['email'] ?? '');

        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new \Exception("user with email {$email} not found");
        }

        $id = $user->id;

        if (Auth::id() === $user->id) {
            Auth::logout();

            if (request()->hasSession()) {
                request()->session()->invalidate();
                request()->session()->regenerateToken();
            }
        }

        $user->delete();

        return [
            'id' => $id,
            'email' => $email,
        ];
    }
}
