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

        // 📁 Где лежат данные MagicPro
        define('MAGIC_DATA_DIR', base_path('/dataMagicPro'));

        // ⚙️ Контроллеры статей (создаются композером с правами www-data)
        define('MAGIC_CONTROLLER_DIR', MAGIC_DATA_DIR . '/controller');

        // 📄 Каталог вьюх статей
        define('MAGIC_VIEW_DIR', MAGIC_DATA_DIR . '/view');

        // 💾 Путь к папке вендор где лежить мпро
        define('VENDOR_FROM', base_path('vendor/dixipro/magicpro/readyBundle/'));

        // 💾 Путь к папке вендор где лежить мпро
        define('VENDOR_PUBLIC', base_path('public/vendor/magicpro/'));

        // 💾 Каталог генерации HTML-кеша, от корня проекта
        define('STATIC_HTML_CREATE_DIR', base_path('storage/app/private/magic/html'));



        // 🔐 Описание директорий и их прав (для проверки и отладки)
        define('MAGIC_FILE_ROLES', [
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

        define('ART_NAME_404', 'error404');
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
        $schema = require self::$dataSchema;
        $defaults = array_map(fn($item) => $item['default'], $schema);
        self::$INI = self::saveIniFile($defaults);
    }

    // сохранить параметры
    public static function saveIniFile($allVars): array
    {
        self::validate(($allVars));

        // заменяем mutable true на установленные значения
        $savedParams = require self::$dataSchema;
        foreach ($savedParams as $key => $value) {
            $allVars[$key] = $value['mutable'] ? $allVars[$key] : $value['default'];
        }

        MagicFile::make()
            ->base()
            ->name(self::$localIniFile)
            ->put("<?php return " . var_export($allVars, true) . ";");

        return require  base_path(self::$localIniFile);
    }

    private static function validate(array $data): void
    {
        $schema = require self::$dataSchema;
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
                case 'string':
                    if (!is_string($value)) {
                        throw new \Exception("$key должно быть строкой");
                    }
                    break;

                default:
                    throw new \Exception("Неизвестный тип для $key");
            }
        }
    }
}
