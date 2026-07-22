<?php

namespace MagicProSrc\Api;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use MagicProSrc\Helpers\MproHelper;
use MagicProDatabaseModels\MagicProEvent;

use Illuminate\Support\Facades\Schema;

class API_Auth extends AbstractApi
{
    /**
     * Centralized error messages for all methods of this class,
     * so they can be handled and displayed in blade.
     * Dynamic parameters (email, seconds, ...) are intentionally omitted.
     */
    protected const ERRORS = [
        'captcha_token_require'     => 'captcha token require',
        'captcha_secret_missing'    => 'RECAPTCHA_SECRET_KEY is not configured',
        'captcha_unavailable'       => 'captcha service unavailable',
        'captcha_failed'            => 'captcha verification failed',
        'decrypt_error'             => 'Decrypt error',
        'invalid_password'          => 'Invalid password',
        'authorization_error'       => 'Authorization error',
        'invalid_email'             => 'Invalid email',
        'password_too_short'        => 'Password must be at least 8 characters',
        'key_expired'               => 'Key expired',
        'key_decryption_failed'     => 'Auth key invalid',
        'too_many_attempts'         => 'too many attempts, try again later',
        'user_already_exists'       => 'user already exists',
        'invalid_email_or_password' => 'invalid email or password',
        'user_not_found'            => 'user not found',
        'user_auth'                 => 'user auth',
        'user_id_required'          => 'user id required',
        'admin_access_required'     => 'Admin access required',
        'email_already_sent'        => 'registration letter has already been sent',
    ];

    protected array $map = [
        'authEmailPassword' => 'authEmailPassword',
        'createUser' => 'createUser',
        'deleteUser' => 'deleteUser',
        'logout' => 'logout',
        'currentUser' => 'currentUser',
        'cryptEmailPass' => 'cryptEmailPass',
        'decryptEmailPass' => 'decryptEmailPass',
        'userInfo' => 'userInfo',
        'processNewUser' => 'processNewUser',
        'checkGoogleCapture' => 'checkGoogleCapture',
        'validateEmail' => 'validateEmail',
        'blade_render_error' => 'blade_render_error',
        'sendAuthEmail'      => 'sendAuthEmail',
        'getStructure'    => 'getStructure',
        'getUserList'     => 'getUserList',
        'editUser'        => 'editUser',
        'chagePassword'   => 'chagePassword',
        'authById'        => 'authById'
    ];


    // ==================================================================
    //                     helper methods
    // ==================================================================

    protected static  function getStructure(array $params): array
    {
        $model = new User();

        $fillable = $model->getFillable();

        $fields = collect(
            Schema::getColumns($model->getTable())
        )
            ->whereIn('name', $fillable)
            ->values()
            ->all();

        return $fields;
    }

    /**
     * User list.
     * Params:
     *   count     — number of records to return
     *   emailPart — substring to search in email ('' — no filter)
     * Sorted by registration date, newest first.
     */
    protected static  function getUserList(array $params): array
    {
        $count = (int) ($params['count'] ?? 0);
        $emailPart = (string) ($params['emailPart'] ?? '');

        $fields = self::getStructure($params);

        // структура даёт fillable-поля (name, email, ...);
        // password не отдаём (хеш), id и created_at добавляем для фронта
        $columns = collect($fields)
            ->pluck('name')
            ->reject(fn($name) => $name === 'password')
            ->prepend('id')
            ->push('created_at')
            ->unique()
            ->all();

        $query = User::query();

        if ($emailPart !== '') {
            $query->where('email', 'like', '%' . $emailPart . '%');
        }

        $users = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($count)
            ->get($columns);

        return [
            'users' => $users->all(),
        ];
    }

    protected static  function sendAuthEmail(array $params): array
    {

        $data = [
            'email' => $params['email'] ?? '',
            'password' => $params['password'] ?? '',
            'back' => $params['back'] ?? '/testSite',
        ];

        /*      параметры
        $token - гугловый токен
        $email - 
        $password -
        $blade - имя блейда
        $authPage - ссылка, передается в $blade
        $back - ссылка, передается в $blade
        $subject - заголовок
    */
        // если нет гугл выкинет исключение
        $token = $params['token'];
        $res = self::run("checkGoogleCapture", [
            "token" => $token,
        ]);

        $email = self::validateEmail($params);
        // нет выкинет исключение

        $password = self::validatePassword($params);
        // нет выкинет исключение

        // Проверить, зарегистрирован ли пользователь
        $userInfo = self::run('userInfo', [
            'email' => $email,
        ]);

        // Пользователь зарегистрирован — пробуем авторизовать
        if ($userInfo['status']) {
            $resAuth = self::run('authEmailPassword', [
                'email' => $email,
                'password' => $password,
                'ip' => request()->ip(),
            ]);
            // если ошибка выкинет исключение, если нет доберется сюда
            return [
                'back' => $data['back'] ?? '/'
            ];
        }

        // нет пользователя
        // проверяем не отправляли ли уже письмо
        // письмо на этот адрес уже отправляли — повторно не шлём
        if (!MagicProEvent::addEvent("mail_{$email}_registration", now()->addMinutes(10))) {
            throw new \Exception(self::ERRORS['email_already_sent']);
        }

        // сгенерить ключ
        $back = trim((string) ($params['back'] ?? '')) ?: '/';
        $resKey = self::run('cryptEmailPass', [
            'email' => $email,
            'password' => $password,
            'back' => $back,
        ]);

        // рендерить шаблон
        $key = $resKey['data']['key'];
        try {
            $html = view($params['blade'], [
                'key' => $key,
                'back' => $back,
                'authPage' => $params['authPage']
            ])->render();
        } catch (\Throwable $e) {
            throw new \Exception(self::ERRORS['blade_render_error' . ' ' . $e]);
        }

        $email = [
            'email' => $email,
            'subj' => $params['subject'] . 'registration letter',
            'html' => $html,
        ];
        $res = \MproHelper::sendMail($email);

        if (!$res['status']) {
            throw new \Exception($res['errorMsg']);
        }

        return [
            'html' => $html
        ];
    }

    protected static  function checkGoogleCapture(array $params): array
    {
        // токен reCAPTCHA, полученный с фронтенда
        $token = (string) ($params['token'] ?? $params['response'] ?? '');

        if ($token === '') {
            throw new \Exception(self::ERRORS['captcha_token_require']);
        }

        $secret = (string) env('RECAPTCHA_SECRET_KEY');

        if ($secret === '') {
            throw new \Exception(self::ERRORS['captcha_secret_missing']);
        }

        // обращаемся к Google для проверки токена
        $res = Http::asForm()
            ->connectTimeout(2)
            ->timeout(4)
            ->post(
                'https://www.google.com/recaptcha/api/siteverify',
                [
                    'secret'   => $secret,
                    'response' => $token,
                    'remoteip' => request()->ip(),
                ]
            );

        if (!$res->successful()) {
            throw new \Exception(self::ERRORS['captcha_unavailable']);
        }

        $data = $res->json();

        if (($data['success'] ?? false) !== true) {
            throw new \Exception(self::ERRORS['captcha_failed']);
        }

        return [
            'verified' => true,
        ];
    }

    // авторизовать пользователя по ключу
    protected static  function processNewUser(array $params): array
    {
        // Пользователь уже авторизован
        if (Auth::check()) {
            throw new \Exception(self::ERRORS['user_auth']);
        }

        // зашифрованный ключ
        $key = (string) ($params['key'] ?? '');

        // Расшифровать входные параметры
        $res = self::run('decryptEmailPass', [
            'key' => $key,
        ]);

        $data = $res['data'];

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
            // если ошибка выкинет исключение, если нет доберется сюда
            return [
                'back' => $data['back'] ?? '/'
            ];
        }

        // Зарегистрировать пользователя
        $resCreate = self::run('createUser', [
            'email' => $data['email'],
            'password' => $data['password'],
        ]);
        // если ошибка выкинет исключение, если нет доберется сюда

        // Авторизовать нового пользователя
        $resAuth = self::run('authEmailPassword', [
            'email' => $data['email'],
            'password' => $data['password'],
            'ip' => request()->ip(),
        ]);

        // если ошибка выкинет исключение, если нет доберется сюда
        return [
            'back' => $data['back'] ?? '/'
        ];
    }



    protected static  function validateEmail(array $params): string
    {
        $email = mb_strtolower(trim($params['email']));

        $validator = Validator::make(
            ['email' => $email],
            ['email' => ['required', 'email:rfc,dns', 'max:255']]
        );

        if ($validator->fails()) {
            throw new \Exception(self::ERRORS['invalid_email']);
        }

        return $email;
    }

    protected static  function validatePassword(array $params): string
    {
        $password = trim($params['password']);

        $validator = Validator::make(
            ['password' => $password],
            ['password' => ['required', 'string', 'min:8', 'max:255']]
        );

        if ($validator->fails()) {
            throw new \Exception(self::ERRORS['password_too_short']);
        }
        return $password;
    }


    protected static  function cryptEmailPass(array $params): array
    {
        $data = [
            'email' => self::validateEmail($params),
            'password' => self::validatePassword($params),
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

    protected static  function decryptEmailPass(array $params): array
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
                throw new \Exception(self::ERRORS['key_expired']);
            }
        } catch (\Throwable $e) {
            throw new \Exception(self::ERRORS['key_decryption_failed'], previous: $e);
        }

        return $data;
    }

    protected static  function logout(array $params): array
    {
        Auth::logout();

        if (request()->hasSession()) {
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }

        return [];
    }

    protected static  function authEmailPassword(array $params): array
    {
        $email = self::validateEmail($params);
        $password = self::validatePassword($params);
        $remember = (bool) ($params['remember'] ?? true);

        $ip = (string) ($params['ip'] ?? request()->ip());

        $key = Str::transliterate(
            'login:' . $email . '|' . $ip
        );
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw new \Exception(self::ERRORS['too_many_attempts']);
        }

        if (!Auth::attempt([
            'email' => $email,
            'password' => $password,
        ], $remember)) {
            RateLimiter::hit($key, 60);
            throw new \Exception(self::ERRORS['invalid_email_or_password']);
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

    /**
     * Authenticate a user by id (without password).
     * Access is guarded at the route level (magic.auth middleware).
     */
    protected static  function authById(array $params): array
    {
        $user = Auth::guard('magic')->user();

        if (!$user || $user->role !== 'admin') {
            throw new \Exception('Admin access required');
        }


        $id = (int) ($params['id'] ?? 0);
        $remember = (bool) ($params['remember'] ?? true);

        if ($id <= 0) {
            throw new \Exception(self::ERRORS['user_id_required']);
        }

        if (!User::whereKey($id)->exists()) {
            throw new \Exception(self::ERRORS['user_not_found']);
        }

        $user = Auth::loginUsingId($id, $remember);

        if (!$user) {
            throw new \Exception(self::ERRORS['authorization_error']);
        }

        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        return [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
        ];
    }

    protected static  function createUser(array $params): array
    {
        $email = self::validateEmail($params);
        $password = self::validatePassword($params);
        $name = trim((string) ($params['name'] ?? ''));

        if (User::where('email', $email)->exists()) {
            throw new \Exception(self::ERRORS['user_already_exists']);
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

    /**
     * Check that the current user may modify the user with the given id.
     * Allowed for the user themselves, otherwise admin access is required.
     */
    protected static  function checkUserAccess(int $id): void
    {
        // свои данные пользователь меняет сам, чужие — только админ
        if (Auth::id() === $id) {
            return;
        }

        $admin = Auth::guard('magic')->user();

        if (!$admin || $admin->role !== 'admin') {
            throw new \Exception(self::ERRORS['admin_access_required']);
        }
    }

    /**
     * Update a user found by id.
     * Allowed for the user themselves, otherwise admin access is required.
     * name / email are updated (email is validated).
     * password: empty — keep current, non-empty — store its hash.
     */
    protected static  function editUser(array $params): array
    {
        $id = (int) ($params['id'] ?? 0);

        if ($id <= 0) {
            throw new \Exception(self::ERRORS['user_id_required']);
        }

        self::checkUserAccess($id);

        $user = User::find($id);

        if (!$user) {
            throw new \Exception(self::ERRORS['user_not_found']);
        }

        $email = self::validateEmail($params);

        if (
            User::where('email', $email)
            ->where('id', '!=', $user->id)
            ->exists()
        ) {
            throw new \Exception(self::ERRORS['user_already_exists']);
        }

        $user->name = trim((string) ($params['name'] ?? ''));
        $user->email = $email;

        $user->save();

        $password = trim((string) ($params['password'] ?? ''));

        // пустой пароль — не меняем, непустой — меняем через chagePassword
        if ($password !== '') {
            self::run('chagePassword', [
                'id' => $user->id,
                'password' => $password,
            ]);
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }

    /**
     * Change the password of a user found by id.
     * Allowed for the user themselves, otherwise admin access is required.
     * The password is validated and stored as a hash.
     */
    protected static  function chagePassword(array $params): array
    {
        $id = (int) ($params['id'] ?? 0);

        if ($id <= 0) {
            throw new \Exception(self::ERRORS['user_id_required']);
        }

        self::checkUserAccess($id);

        $user = User::find($id);

        if (!$user) {
            throw new \Exception(self::ERRORS['user_not_found']);
        }

        $user->password = Hash::make(self::validatePassword($params));

        $user->save();

        return [
            'id' => $user->id,
            'email' => $user->email,
        ];
    }

    protected static  function deleteUser(array $params): array
    {
        $email = (string) ($params['email'] ?? '');

        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new \Exception(self::ERRORS['user_not_found']);
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

    protected static  function currentUser(array $params): array
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

    protected static  function userInfo(array $params): array
    {
        $email = self::validateEmail($params);

        $user = User::where('email', $email)
            ->first(['name', 'email', 'created_at']);

        if (!$user) {
            throw new \Exception(self::ERRORS['user_not_found']);
        }

        return [
            'email' => $user->email,
            'name' => $user->name,
            'created_at' => $user->created_at,
        ];
    }
}
