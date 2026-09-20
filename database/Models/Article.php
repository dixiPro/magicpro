<?php

namespace MagicProDatabaseModels; // в композере прописывается

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Article extends Model
{
    /**
     * Route options of an article, one set for every way an article is born:
     * the editor, the installer, МСП, the import, the «create» button.
     *
     * There used to be three: the editor turned utm on when it opened an
     * article, the installer and МСП left it off, and a new article had none
     * at all. Two articles made the same way behaved differently — one opened
     * from an ad link, the other gave 404 — depending on who touched it first.
     *
     * utm is on: a link from an ad must not break the page.
     */
    public const ROUTE_PARAMS = [
        'useController'   => false,
        'adminOnly'       => false,
        'utmParamsEnable' => true,
        'getEnable'       => false,
        'postEnable'      => false,
        'bindKeys'        => false,
        'keysArr'         => [],
        'livewire'        => false,
    ];

    /**
     * Route options with every key in place.
     *
     * A missing key takes its default, a present one is brought to its type.
     * The router reads the keys without a fallback, and a half-filled array —
     * `{"useController": false}` saved by a script — used to turn a published
     * article into 404. Keys this list does not know are kept as they are.
     */
    public static function routeParams(mixed $params): array
    {
        $params = array_replace(self::ROUTE_PARAMS, is_array($params) ? $params : []);

        foreach (self::ROUTE_PARAMS as $key => $default) {
            $params[$key] = is_array($default)
                ? array_values(array_map('strval', array_filter((array) $params[$key], 'is_scalar')))
                : (bool) $params[$key];
        }

        return $params;
    }

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
            // every key in place, whoever is saving
            $m->routeParams = self::routeParams($m->routeParams);

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
