<?php

namespace MagicProDatabaseModels; // в композере прописывается

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

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
            // Нормализуем parentId
            $m->parentId = (int)($m->parentId ?? 0);

            // Корень всегда parentId = 0
            if ((int)($m->id ?? 0) === 1 && $m->parentId !== 0) {
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

    // public function setRouteParamsAttribute($v): void
    // {
    //     $this->attributes['routeParams'] = is_string($v) ? (json_decode($v, true) ?? []) : ($v ?? []);
    // }

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
