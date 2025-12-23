<?php

namespace MagicProSrc;

use Illuminate\Support\Facades\Blade;
use RuntimeException;

class MagicLang
{
    protected static string $locale = '';
    protected static array $data = [];
    protected static bool $bladeIsRegister = false;

    public static function loadLocale(string $locale): void
    {

        try {

            $file = __dir__ . '/../lang/' . $locale . '/messages.php';

            if (!is_file($file)) {
                $file = __dir__ . '/../lang/' . 'ru' . '/messages.php';
            }

            if (!is_file($file)) {
                throw new RuntimeException("lang file not found: {$file}");
            }

            self::$locale = $locale;
            self::$data = require $file;

            if (!self::$bladeIsRegister) {
                Blade::directive('magic_msg', function ($key) {
                    return "<?php echo \\MagicProSrc\\MagicLang::getMsg($key); ?>";
                });

                self::$bladeIsRegister = true;
            }
        } catch (\Throwable $e) {
            $a = 1;
            throw new RuntimeException(
                $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(),
                0,
                $e
            );
        }
    }

    public static function getMsg(string $key): string
    {
        return self::$data[$key] ?? '';
    }
}
