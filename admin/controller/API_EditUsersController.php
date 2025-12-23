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
            $msg = $th->getMessage();
            if ($th->getFile()) $msg .= ' in ' . $th->getFile();
            if ($th->getLine()) $msg .= ' on line ' . $th->getLine();

            return response()->json([
                'status'   => false,
                'errorMsg' => $msg,
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
    private function addUser(Request $request): array
    {
        $data = (array) $request->input('data');
        $id = (int)($data['id'] ?? 0);

        $user = new MagicProUser();

        $user->fill($data);
        $user->password = bcrypt($request->string(Str::random(10)));
        $user->save();

        return $user->toArray();
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
