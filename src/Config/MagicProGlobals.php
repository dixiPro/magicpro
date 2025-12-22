<?php
// 
namespace MagicProSrc\Config;

use RuntimeException;

/**
 * Класс для централизованного определения всех глобальных констант MagicPro.
 * Регистрируется в ServiceProvider и делает константы доступными во всём приложении.
 */

/**
 * Регистрирует все глобальные константы MagicPro.
 * Вызывать из MagicServiceProvider::boot().
 */

class MagicProGlobals
{
    public static array $INI = [
        // 📁 Где лежат данные MagicPro
        'MAGIC_DATA_DIR' => '/dataMagicPro',

        // ⚙️ Контроллеры статей (создаются композером с правами www-data)
        'MAGIC_CONTROLLER_DIR' => '/dataMagicPro/controller',

        // 📄 Каталог вьюх статей
        'MAGIC_VIEW_DIR' => '/dataMagicPro/view',

        // 💾 Путь к папке вендор где лежить мпро
        'VENDOR_FROM' => '/vendor/dixipro/magicpro/readyBundle/',

        // 💾 Путь к папке вендор где лежить мпро
        'VENDOR_PUBLIC' =>  '/public/vendor/magicpro/',

        // статья с ошибкой 404
        'ART_NAME_404' => 'error404',

        // файл настроек
        'LOCAL_INI_FILE' => '/storage/app/private/magic/mproLocalIni2.php',

        // настройки по умолчанию
        'SCHEMA_FILE' =>  __DIR__ . '/magicProSchema.php',

        // сторадж папка где лежит 
        'STORAGE_DIR' => '/storage/app/private/magic',

    ];


    // загрузка локальных настроек
    public  static function loadLocal(): void
    {
        if (!file_exists(self::$INI['LOCAL_INI_FILE'])) {
            self::createIniFile();
        }
        $localParams = include base_path(self::$INI['LOCAL_INI_FILE']);
        self::$INI = array_merge($localParams, self::$INI);
    }

    // сохранить параметры по умолчанию
    private static function createIniFile(): void
    {
        $schema = require self::$INI['SCHEMA_FILE'];
        $defaults = array_map(fn($item) => $item['default'], $schema);
        self::saveIniFile($defaults);
    }

    // сохранить параметры
    public static function saveIniFile($allVars): array
    {
        self::validate(($allVars));

        $filename = self::$INI['LOCAL_INI_FILE'];
        $content = "<?php return " . var_export($allVars, true) . ";";

        self::saveToBasePathFile($filename, $content);

        return require base_path(self::$INI['LOCAL_INI_FILE']);
    }

    // сохранить параметры
    public static function saveToBasePathFile($filename, $content): void
    {
        $filename = base_path($filename);
        $dir = dirname($filename);

        if (!is_dir($dir)) {
            if (false === @mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new RuntimeException(sprintf('Unable to create directory "%s".', $dir));
            }
        }
        if (false === @file_put_contents($filename, $content)) {
            throw new RuntimeException(sprintf('Failed to write file "%s".', $filename));
        }

        return;
    }

    private static function validate(array $data): void
    {
        $schema = require self::$INI['SCHEMA_FILE'];
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
