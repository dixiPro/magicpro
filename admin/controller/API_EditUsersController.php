<?php

namespace MagicProAdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use MagicProDatabaseModels\MagicProUser;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;


class API_EditUsersController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        try {
            $methods = [
                'getUserList' => ['name' => 'getUserList'],
                'addUser'     => ['name' => 'addUser'],
                'editUser'    => ['name' => 'editUser'],
                'deleteUser'  => ['name' => 'deleteUser'],
            ];

            $command = $request->string('command')->toString();

            if (!array_key_exists($command, $methods)) {
                throw new \InvalidArgumentException("Unknown command '{$command}'");
            }

            $methodName = $methods[$command]['name'];
            if (!method_exists($this, $methodName)) {
                throw new \BadMethodCallException("Method {$methodName} not found");
            }

            $data = $this->{$methodName}($request);

            return response()->json([
                'status'  => true,
                'data'    => $data,
                'request' => $request->all(),
            ]);
        } catch (\Throwable $th) {
            // место ошибки — в лог, наружу только текст
            \MproHelper::addLog('api', [
                'api'     => static::class,
                'command' => $request->string('command')->toString(),
                'error'   => $th->getMessage(),
                'where'   => $th->getFile() . ' ' . $th->getLine(),
            ]);

            return response()->json([
                'status'   => false,
                'errorMsg' => $th->getMessage(),
                'request'  => $request->all(),
            ]);
        }
    }

    // ================================
    // 📋 user list
    private function getUserList(Request $request): array
    {
        return MagicProUser::select('id', 'name', 'email', 'role', 'created_at', 'updated_at')
            ->orderBy('id')
            ->get()
            ->toArray();
    }

    // ================================
    // ➕ add user
    /**
     * A new admin with a password made here.
     *
     * The password used to be `bcrypt($request->string(Str::random(10)))` — a
     * field of the request under a random name, that is nothing, so every
     * admin added from the screen got the hash of an empty string and signed
     * in with an empty password field.
     *
     * Now it is random, twelve letters and digits — easy to copy, no symbol to
     * lose in a messenger. The answer carries it once, as text; the base keeps
     * only the hash, so it cannot be shown again: a forgotten one is replaced
     * by editing.
     */
    private function addUser(Request $request): array
    {
        $data = (array) $request->input('data');

        // a password never comes from the form here, and the id is the base's
        unset($data['password'], $data['id']);

        $password = Str::password(12, symbols: false);

        $user = new MagicProUser();

        $user->fill($data);
        $user->password = Hash::make($password);
        $user->save();

        return $user->only(['id', 'name', 'email', 'role', 'created_at', 'updated_at'])
            + ['password' => $password];
    }

    // ================================
    // 💾 save (edit) user
    private function editUser(Request $request): array
    {
        $data = (array) $request->input('data');
        $id = (int)($data['id'] ?? 0);

        $user = MagicProUser::find($id);
        if (!$user) {
            throw new \RuntimeException("user #{$id} not found");
        }

        $user->fill([
            'name'  => $data['name'],
            'email' => $data['email'],
            'role'  => $data['role'] ?? $user->role,
        ]);

        if (!empty($data['password'])) {
            // the model sees only the hash: the length is checked while it is text
            if (mb_strlen((string) $data['password']) < MagicProUser::PASSWORD_MIN) {
                throw new \InvalidArgumentException('password must be at least ' . MagicProUser::PASSWORD_MIN . ' characters');
            }

            $user->password = Hash::make($data['password']);
        }

        $user->save();

        $user = MagicProUser::find($id);

        return $user->only(['id', 'name', 'email', 'role', 'created_at', 'updated_at']);
    }



    // ================================
    // ❌ delete user
    private function deleteUser(Request $request): array
    {

        $data = (array) $request->input('data');
        $id = (int)($data['id'] ?? 0);

        if ($id === 1) {
            throw new \RuntimeException('deleting user #1 is forbidden');
        }

        $user = MagicProUser::find($id);
        if (!$user) {
            throw new \RuntimeException("user #{$id} not found");
        }

        $user->delete();

        return ['deleted' => $id];
    }
}
