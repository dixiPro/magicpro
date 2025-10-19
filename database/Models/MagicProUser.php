<?php

namespace MagicProDatabaseModels;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Validator;

class MagicProUser extends Authenticatable
{
    protected $table = 'magicPro_users';

    protected $fillable = ['name', 'email', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($user) {
            // Если пользователь с id = 1 — всегда роль admin
            if ($user->id === 1) {
                $user->role = 'admin';
            }

            $user->validateSelf();
        });

        static::deleting(function ($user) {
            if ($user->id === 1) {
                throw new \RuntimeException('Deletion of user #1 is prohibited at the model level.');
            }
        });
    }

    /**
     * Validate the model before saving.
     *
     * @throws \InvalidArgumentException
     */
    public function validateSelf(): void
    {
        $id = $this->id ?? 0;

        $data = $this->attributesToArray();

        $rules = [
            'name'     => 'required|string|max:255',
            'email'    => "required|email|unique:magicPro_users,email,{$id}",
            'password' => 'sometimes|nullable|string|min:4',
            'role'     => 'nullable|in:admin,user',
        ];

        if ($id > 0) {
            $rules['id'] = 'required|integer|exists:magicPro_users,id';
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            $lines = [];
            foreach ($errors as $field => $messages) {
                $lines[] = "{$field} — " . implode(', ', $messages);
            }
            throw new \InvalidArgumentException(implode("\n", $lines));
        }
    }
}
