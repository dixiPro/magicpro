<?php

namespace MagicProSrc\Config;

use MagicProSrc\MagicFile;

/**
 * Класс для централизованного определения всех глобальных констант MagicPro.
 * Регистрируется в ServiceProvider и делает константы доступными во всём приложении.
 */

/**
 * Регистрирует все глобальные константы MagicPro.
 * Вызывать из MagicServiceProvider::boot().
 */

class MagicGlobals
{

    public static string $magicStorageDir = 'storage/app/private/magic/';
    public static string $localIniFile = 'storage/app/private/magic/mproLocalIni.php';
    public static string $dataSchema = __DIR__ . '/magicSchema.php';
    public static array $INI = [];

    public static function register(): void
    {
        // загрузить файлы из локального ини
        self::loadLocal();

        require_once __DIR__ . '/version.php';

        // TODO: перевести MAGIC_* и прочие константы на config('magicpro.*').
        // Сейчас register() может вызываться повторно (тесты PHPUnit поднимают
        // приложение заново на каждый метод), а голый define() на второй раз
        // роняет E_WARNING "already defined". Как временный минимальный фикс
        // константы определяются через defined() || define(). Правильный путь —
        // config-репозиторий: переопределяется в тестах, кэшируется, не течёт
        // в глобальное пространство имён. Это отдельная задача (обойти все
        // использования MAGIC_* по пакету), не делать попутно.

        // 📁 Где лежат данные MagicPro
        defined('MAGIC_DATA_DIR') || define('MAGIC_DATA_DIR', base_path('/storage/dataMagicPro'));

        // ⚙️ Контроллеры статей (создаются композером с правами www-data)
        defined('MAGIC_CONTROLLER_DIR') || define('MAGIC_CONTROLLER_DIR', MAGIC_DATA_DIR . '/controller');

        // 📄 Каталог вьюх статей
        defined('MAGIC_VIEW_DIR') || define('MAGIC_VIEW_DIR', MAGIC_DATA_DIR . '/view');

        // 💾 Путь к папке вендор где лежить мпро
        defined('VENDOR_FROM') || define('VENDOR_FROM', base_path('vendor/dixipro/magicpro/readyBundle/'));

        // 💾 Путь к папке вендор где лежить мпро
        defined('VENDOR_PUBLIC') || define('VENDOR_PUBLIC', base_path('public/vendor/dixipro/magicpro'));

        // 💾 Каталог генерации HTML-кеша, от корня проекта
        defined('STATIC_HTML_CREATE_DIR') || define('STATIC_HTML_CREATE_DIR', base_path('storage/app/private/magic/html'));



        // 🔐 Описание директорий и их прав (для проверки и отладки)
        defined('MAGIC_FILE_ROLES') || define('MAGIC_FILE_ROLES', [
            [
                'value' => MAGIC_VIEW_DIR,
                'desc'  => 'Directory for view'
            ],
            [
                'value' => MAGIC_CONTROLLER_DIR,
                'desc'  => 'Directory for controllers'
            ],
            [
                'value' => public_path(MagicGlobals::$INI['PUBLIC_UPLOAD_DIR']),
                'desc'  => 'Directory for public'
            ],
        ]);

        defined('ART_NAME_404') || define('ART_NAME_404', 'error404');
    }

    // загрузка локальных настроек
    private  static function loadLocal(): void
    {
        if (!file_exists(base_path(self::$localIniFile))) {
            self::saveDefaultIniFile();
        }
        self::$INI = include base_path(self::$localIniFile);
    }

    // сохранить параметры по умолчанию
    private static function saveDefaultIniFile(): void
    {
        self::$INI = self::saveIniFile(self::defaults(require self::$dataSchema));
    }

    /** Умолчания схемы. Группа отдаёт вложенный массив своих полей. */
    private static function defaults(array $schema): array
    {
        $out = [];

        foreach ($schema as $key => $item) {
            $out[$key] = ($item['type'] ?? '') === 'group'
                ? self::defaults($item['data'] ?? [])
                : $item['default'];
        }

        return $out;
    }

    // сохранить параметры
    public static function saveIniFile($allVars): array
    {
        self::validate(($allVars));

        // заменяем mutable true на установленные значения
        $allVars = self::applyMutable(require self::$dataSchema, $allVars);

        MagicFile::make()
            ->base()
            ->name(self::$localIniFile)
            ->put("<?php return " . var_export($allVars, true) . ";");

        return require  base_path(self::$localIniFile);
    }

    /**
     * Немутабельному параметру возвращается умолчание, чем бы его ни пытались
     * заменить. Значения, которого в файле ещё нет, тоже берутся из умолчаний:
     * так добавленный в схему параметр начинает работать сразу.
     */
    private static function applyMutable(array $schema, array $values): array
    {
        foreach ($schema as $key => $item) {
            if (($item['type'] ?? '') === 'group') {
                $values[$key] = self::applyMutable($item['data'] ?? [], (array) ($values[$key] ?? []));

                continue;
            }

            $values[$key] = $item['mutable'] ? ($values[$key] ?? $item['default']) : $item['default'];
        }

        return $values;
    }

    private static function validate(array $data): void
    {
        self::validateAgainst(require self::$dataSchema, $data);
    }

    private static function validateAgainst(array $schema, array $data): void
    {
        foreach ($data as $key => $value) {

            if (!array_key_exists($key, $schema)) {
                throw new \Exception("Неизвестная настройка: $key");
            }

            $type = $schema[$key]['type'];

            switch ($type) {
                case 'boolean':
                    if (!is_bool($value)) {
                        throw new \Exception("$key должно быть boolean");
                    }
                    break;

                case 'localpath':
                    if (!is_string($value) || $value === '') {
                        throw new \Exception("$key должно быть непустой строкой");
                    }
                    break;

                case 'array':
                    if (!is_array($value)) {
                        throw new \Exception("$key должно быть массивом");
                    }
                    break;

                    // группа: значение — массив своих полей, каждое проверяется
                    // по вложенной схеме
                case 'group':
                    if (!is_array($value)) {
                        throw new \Exception("$key должно быть массивом");
                    }

                    self::validateAgainst($schema[$key]['data'] ?? [], $value);
                    break;
                case 'string':
                    if (!is_string($value)) {
                        throw new \Exception("$key должно быть строкой");
                    }
                    break;

                    // выбор из готового списка: значение вне списка означает
                    // настройку, которой в системе нет, и молча принимать её нельзя
                case 'list':
                    $values = $schema[$key]['values'] ?? [];

                    if (!in_array($value, $values, true)) {
                        throw new \Exception("$key должно быть одним из: " . implode(', ', $values));
                    }
                    break;

                    // с экрана число приходит строкой, поэтому сравниваем по значению,
                    // а не по типу
                case 'integer':
                    if (!is_numeric($value) || (int) $value != $value) {
                        throw new \Exception("$key должно быть целым числом");
                    }

                    $min = $schema[$key]['min'] ?? null;
                    $max = $schema[$key]['max'] ?? null;

                    if ($min !== null && (int) $value < $min) {
                        throw new \Exception("$key не меньше $min");
                    }

                    if ($max !== null && (int) $value > $max) {
                        throw new \Exception("$key не больше $max");
                    }
                    break;

                default:
                    throw new \Exception("Неизвестный тип для $key");
            }
        }
    }
}
