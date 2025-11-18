<?php

namespace MagicProSrc\Config;

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

    private static string $localIniFile = 'storage/app/private/mproLocalIni.php';
    public static array $INI = [];

    private static array $schema = [

        'STATIC_HTML_DIR' => [
            'label'   => 'Каталог HTML-кеша',
            'type'    => 'localpath',
            'default' => 'html', // от папки public
            'mutable' => false,
        ],

        'STATIC_HTML_CREATE_DIR' => [
            'label'   => 'Каталог генерации HTML-кеша',
            'type'    => 'localpath', // без слеша в на конце
            'default' => 'html__', // от папки public
            'mutable' => true,
        ],

        'STATIC_HTML_ENABLE' => [
            'label'   => 'Статический HTML-кеш',
            'type'    => 'boolean', // без слеша в на конце
            'default' => true,
            'mutable' => false,
        ],

        'HOST_DEV' => [
            'label'   => 'Сервер разработки',
            'type'    => 'string', // без слеша в на конце
            'default' => 'mpro2.test',
            'mutable' => true,
        ],

        'EXCLUDED_ROUTES' => [
            'label'   => 'Страницы исключенные из динамического раута',
            'type'    => 'array', // без слеша в на конце
            'default' => [
                'livewire',
                'telescope',
                'horizon',
                'nova',
                'debugbar',
                'admin',
                'public',
                'f_ilament',
                'storage'
            ],
            'mutable' => true,
        ],
    ];
    public static function register(): void
    {
        // загрузить файлы из локального ини
        self::loadLocal();

        // 📁 Где лежат данные MagicPro
        define('MAGIC_DATA_DIR', base_path('dataMagicPro'));

        // ⚙️ Контроллеры статей (создаются композером с правами www-data)
        define('MAGIC_CONTROLLER_DIR', MAGIC_DATA_DIR . '/controller');

        // 📄 Каталог вьюх статей
        define('MAGIC_VIEW_DIR', MAGIC_DATA_DIR . '/view');

        // 📦 Папка для загрузки изображений (относительный путь для JS)
        define('FILES_JS_UPLOAD', 'design');

        // 💾 Абсолютный путь к папке загрузок на сервере
        define('FILES_UPLOAD_DIRECTORY', base_path('public') . '/' . FILES_JS_UPLOAD);

        // 💾 Путь к папке вендор где лежить мпро
        define('VENDOR_FROM', base_path('vendor/dixipro/magicpro/readyBundle/'));

        // 💾 Путь к папке вендор где лежить мпро
        define('VENDOR_PUBLIC', base_path('public/vendor/magicpro/'));



        // 🔐 Описание директорий и их прав (для проверки и отладки)
        define('MAGIC_FILE_ROLES', [
            [
                'value' => MAGIC_VIEW_DIR,
                'desc'  => 'Каталог вьюх статей'
            ],
            [
                'value' => MAGIC_CONTROLLER_DIR,
                'desc'  => 'Контроллеры статей'
            ],
            [
                'value' => FILES_UPLOAD_DIRECTORY,
                'desc'  => 'Файлы на сервере'
            ],
        ]);

        define('ART_NAME_404', 'error404');

        define('ENABLE_URL_PARAMS', [
            // Стандартные UTM
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_term',
            'utm_content',

            // Рекламные идентификаторы
            'gclid',     // Google Ads
            'fbclid',    // Facebook / Instagram
            'yclid',     // Яндекс.Директ
            'ttclid',    // TikTok Ads
            'msclkid',   // Microsoft Ads (Bing)

            // Альтернативные трекинги
            '_openstat', // Яндекс, Mail.ru
            // 'aff_id',    // Партнёрские ID
            // 'ref',
            // 'partner_id',
            // 'click_id',
            // 'cid',
            // 'track_id',
        ]);

        define('EXCLUDED_ROUTES', [
            'livewire',
            'telescope',
            'horizon',
            'nova',
            'debugbar',
            'admin',
            'public',
            'f_ilament',
            'storage'
        ]);
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
        $defaults = array_map(fn($item) => $item['default'], self::$schema);
        self::$INI = self::saveIniFile($defaults);
    }

    // сохранить параметры
    public static function saveIniFile($allVars): array
    {
        self::validate(($allVars));
        file_put_contents(
            base_path(self::$localIniFile),
            "<?php return " . var_export($allVars, true) . ";"
        );
        return require  base_path(self::$localIniFile);
    }

    public static function saveKey($key, $value): array
    {
        self::validate(([$key => $value]));
        self::$INI[$key] = $value;
        file_put_contents(
            base_path(self::$localIniFile),
            "<?php return " . var_export(self::$INI, true) . ";"
        );
        return self::$INI;
    }

    private static function validate(array $data): void
    {
        foreach ($data as $key => $value) {

            if (!array_key_exists($key, self::$schema)) {
                throw new \Exception("Неизвестная настройка: $key");
            }

            $type = self::$schema[$key]['type'];

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
