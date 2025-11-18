<?php

namespace MagicProSrc\Config;

/**
 * Класс для централизованного определения всех глобальных констант MagicPro.
 * Регистрируется в ServiceProvider и делает константы доступными во всём приложении.
 */
class MagicGlobals
{
    /**
     * Регистрирует все глобальные константы MagicPro.
     * Вызывать из MagicServiceProvider::boot().
     */
    public static function register(): void
    {
        // загрузить файлы из локального ини



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

        define('CACHE_DIR', base_path('public/html'));
        define('CACHE_CREATE_DIR', base_path('public/html__'));
    }

    // загрузка локальных настроек
    public static function loadLocal(): void
    {

        // сделать так что бы все настройки попадали в Константы
    }

    // создать файл если его нет
    public static function createLocalIni(): void
    {

        // здесь параметры по умолчанию  тогда сюда можно перенести почти все настнойки из метода  register

    }

    public static function getVar(string $key): void {}

    public static function settVar(string $key): void {}
}
