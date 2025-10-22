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
        // 📁 Где лежат данные MagicPro
        define('MAGIC_DATA_DIR', base_path('dataMagicPro'));

        // ⚙️ Контроллеры статей (создаются композером с правами www-data)
        define('MAGIC_CONTROLLER_DIR', MAGIC_DATA_DIR . '/controller');

        // 📄 Каталог вьюх статей
        define('MAGIC_VIEW_DIR', MAGIC_DATA_DIR . '/view');

        // 📦 Папка для загрузки изображений (относительный путь для JS)
        define('FILES_JS_UPLOAD', 'magicPro');

        // 💾 Абсолютный путь к папке загрузок на сервере
        define('FILES_UPLOAD_DIRECTORY', base_path('public' . FILES_JS_UPLOAD));

        // 📚 Список директорий, которые должны существовать
        define('MAGIC_DIRECTORIES', [
            MAGIC_DATA_DIR,
            MAGIC_VIEW_DIR,
            MAGIC_CONTROLLER_DIR,
            FILES_UPLOAD_DIRECTORY,
        ]);


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
    }

    /**
     * Безопасно определяет константу, если она ещё не существует.
     *
     * @param string $name  Имя константы.
     * @param mixed  $value Значение константы.
     */
    private static function defineOnce(string $name, mixed $value): void
    {
        if (!defined($name)) {
            define($name, $value);
        }
    }
}
