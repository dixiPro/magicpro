<?php

namespace MagicProDatabaseModels; // в композере прописывается

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class Article extends Model
{
    protected $fillable = [
        'parentId',
        'npp',
        'name',
        'title',
        'controller',
        'body',
        'directory',
        'menuOn',
        'isRoute',
        'routeParams',
    ];

    protected $casts = [
        'parentId'    => 'integer',
        'npp'         => 'integer',
        'directory'   => 'boolean',
        'menuOn'      => 'boolean',
        'isRoute'     => 'boolean',
        'routeParams' => 'array',
    ];

    protected $attributes = [
        'parentId'   => 0,
        'npp'        => 0,
        'name'       => '',
        'title'      => '',
        'controller' => '',
        'body'       => '',
        'directory'  => false,
        'menuOn'     => false,
        'isRoute'    => false,
        // 'routeParams' => '[]',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $m) {
            // проверка прав

            $guard = Auth::guard('magic');
            if (!$guard->check()) {
                throw ValidationException::withMessages([
                    'parentId' => 'Авторизация пффф....',
                ]);
            }
            $user = $guard->user();
            $roles = ['admin', 'editor'];
            if (!in_array($user->role, $roles)) {
                throw ValidationException::withMessages([
                    'parentId' => 'Недостаточно прав',
                ]);
            }

            // Корень всегда parentId = 0
            if ($m->id == 1) {

                // Запрещаем менять name если запись рут
                if ($m->name !== 'root') {
                    throw ValidationException::withMessages([
                        'parentId' => 'Имя записи с id=1 должно быть "root" и не может быть изменено.',
                    ]);
                }
                // Принудительно фиксируем имя
                $m->name = 'root';
                $m->parentId = 0;
            }

            // Запрет самоссылки
            if (($m->id ?? null) !== null && (int)$m->parentId === (int)$m->id) {
                throw ValidationException::withMessages([
                    'parentId' => 'Статья не может ссылаться на саму себя (parentId == id).',
                ]);
            }

            // Если указан родитель — он должен существовать
            if ($m->parentId > 0) {
                $exists = self::where('id', $m->parentId)->exists();
                if (!$exists) {
                    throw ValidationException::withMessages([
                        'parentId' => "Родитель id={$m->parentId} не найден.",
                    ]);
                }
            }

            // Проверка допустимых символов в name
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $m->name)) {
                throw ValidationException::withMessages([
                    'name' => 'Имя статьи может содержать только латинские буквы, цифры, дефис и подчёркивание.',
                ]);
            }

            // Проверка уникальности name
            $exists = self::where('name', $m->name)
                ->when($m->exists, fn($q) => $q->where('id', '!=', $m->id))
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'name' => "Статья с именем '{$m->name}' уже существует.",
                ]);
            }
        });
    }

    // === строковые поля: не допускаем NULL ===
    public function setNameAttribute($v): void
    {
        $this->attributes['name'] = trim((string)($v ?? ''));
    }

    public function setTitleAttribute($v): void
    {
        $this->attributes['title'] = (string)($v ?? '');
    }

    public function setControllerAttribute($v): void
    {
        $this->attributes['controller'] = (string)($v ?? '');
    }

    public function setBodyAttribute($v): void
    {
        $this->attributes['body'] = (string)($v ?? '');
    }

    // === связи для MoonShine и Eloquent ===
    public function parent()
    {
        return $this->belongsTo(self::class, 'parentId');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parentId');
    }
}
