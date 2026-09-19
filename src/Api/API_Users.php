<?php

namespace MagicProSrc\Api;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Users of the site: the list, editing, "log in as", deleting.
 *
 * No public HTTP address: the admin screen «Пользователи» calls it through
 * `/a_dmin/api/laravelUsers`, a blade — through `API_Users::run()`. Sign-up and
 * sign-in of visitors live in API_SiteAuth.
 */
class API_Users extends AbstractApi
{
    use SiteUserTools;

    /**
     * Centralized error messages for all methods of this class,
     * so they can be handled and displayed in blade.
     * Dynamic parameters (email, seconds, ...) are intentionally omitted.
     */
    protected const ERRORS = [
        'authorization_error'       => 'Authorization error',
        'invalid_email'             => 'Invalid email',
        'password_too_short'        => 'Password must be at least 8 characters',
        'too_many_attempts'         => 'too many attempts, try again later',
        'user_already_exists'       => 'user already exists',
        'invalid_email_or_password' => 'invalid email or password',
        'user_not_found'            => 'user not found',
        'user_id_required'          => 'user id required',
        'admin_access_required'     => 'Admin access required',
    ];

    protected array $map = [
        'getStructure'      => 'getStructure',
        'getUserList'       => 'getUserList',
        'userInfo'          => 'userInfo',
        'currentUser'       => 'currentUser',
        'authEmailPassword' => 'authEmailPassword',
        'authById'          => 'authById',
        'logout'            => 'logout',
        'createUser'        => 'createUser',
        'editUser'          => 'editUser',
        'changePassword'    => 'changePassword',
        'deleteUser'        => 'deleteUser',
    ];

    protected static function getStructure(array $params): array
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
    protected static function getUserList(array $params): array
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

    protected static function userInfo(array $params): array
    {
        $email = self::validEmail($params);

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

    protected static function currentUser(array $params): array
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

    protected static function authEmailPassword(array $params): array
    {
        $email = self::validEmail($params);
        $password = trim((string) ($params['password'] ?? ''));
        $remember = (bool) ($params['remember'] ?? true);

        $error = self::attemptLogin($email, $password, $remember);

        if ($error === 'too_many_attempts') {
            throw new \Exception(self::ERRORS['too_many_attempts']);
        }

        if ($error !== '') {
            throw new \Exception(self::ERRORS['invalid_email_or_password']);
        }

        $user = Auth::user();

        return [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
        ];
    }

    /**
     * Authenticate a user by id (without password). Only an admin of MagicPro may.
     */
    protected static function authById(array $params): array
    {
        self::requireAdmin();

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

        self::regenerateSession();

        return [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
        ];
    }

    protected static function logout(array $params): array
    {
        Auth::logout();

        if (request()->hasSession()) {
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }

        return [];
    }

    protected static function createUser(array $params): array
    {
        $email = self::validEmail($params);
        $password = self::validPassword($params);
        $name = trim((string) ($params['name'] ?? ''));

        if (User::where('email', $email)->exists()) {
            throw new \Exception(self::ERRORS['user_already_exists']);
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        return [
            'id' => $user->id,
            'email' => $user->email,
        ];
    }

    /**
     * Update a user found by id.
     * Allowed for the user themselves, otherwise admin access is required.
     * name / email are updated (email is validated).
     * password: empty — keep current, non-empty — store its hash.
     */
    protected static function editUser(array $params): array
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

        $email = self::validEmail($params);

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

        // пустой пароль — не меняем, непустой — меняем через changePassword
        if ($password !== '') {
            self::runOrFail('changePassword', [
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
     */
    protected static function changePassword(array $params): array
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

        $user->password = Hash::make(self::validPassword($params));

        $user->save();

        return [
            'id' => $user->id,
            'email' => $user->email,
        ];
    }

    /**
     * Delete a user found by email. Only an admin of MagicPro may.
     *
     * Without the check any article that passed an email from the request
     * would let anybody delete anybody.
     */
    protected static function deleteUser(array $params): array
    {
        self::requireAdmin();

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

    // ==================================================================
    //                     helper methods
    // ==================================================================

    protected static function validEmail(array $params): string
    {
        return self::cleanEmail($params['email'] ?? '')
            ?? throw new \Exception(self::ERRORS['invalid_email']);
    }

    protected static function validPassword(array $params): string
    {
        return self::cleanPassword($params['password'] ?? '')
            ?? throw new \Exception(self::ERRORS['password_too_short']);
    }

    protected static function requireAdmin(): void
    {
        $admin = Auth::guard('magic')->user();

        if (!$admin || $admin->role !== 'admin') {
            throw new \Exception(self::ERRORS['admin_access_required']);
        }
    }

    /**
     * Check that the current user may modify the user with the given id.
     * Allowed for the user themselves, otherwise admin access is required.
     */
    protected static function checkUserAccess(int $id): void
    {
        // свои данные пользователь меняет сам, чужие — только админ
        if (Auth::id() === $id) {
            return;
        }

        self::requireAdmin();
    }
}
