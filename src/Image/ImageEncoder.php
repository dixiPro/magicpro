<?php

namespace MagicProSrc\Image;

/**
 * Запуск внешних кодировщиков: webp делает cwebp, остальное — vipsthumbnail.
 *
 * Ни та, ни другая утилита не умеет stdin и stdout, поэтому работаем через файлы.
 * На каждый формат свой метод, общий только `run()`. Исключений нет: картинка не
 * должна ронять страницу.
 */
class ImageEncoder
{
    /** Форматы, которые умеем делать. `iwebp` — тот же webp, но кодирует vips. */
    public const FORMATS = ['webp', 'iwebp', 'avif', 'jpg', 'png'];

    /**
     * Опции webpsave для vipsthumbnail, кроме качества — оно приходит параметром.
     * Вынесено сюда, чтобы подбирать не залезая в метод.
     *
     * preset: default, picture, photo, drawing, icon, text. effort: 0–6.
     */
    private const WEBP_VIPS = 'preset=photo,effort=6,smart-subsample=true';

    /**
     * Уменьшить $file по стороне $axis ('x' — ширина, 'y' — высота) до $size.
     *
     * Вторую сторону считает сама утилита: наша арифметика тут может только
     * разойтись с её округлением.
     */
    public static function make(
        string $file,
        string $target,
        string $axis,
        int $size,
        string $format,
        int $quality
    ): array {
        return match ($format) {
            'webp'  => self::webp($file, $target, $axis, $size, $quality),
            'iwebp' => self::iwebp($file, $target, $axis, $size, $quality),
            'avif' => self::avif($file, $target, $axis, $size, $quality),
            'jpg'  => self::jpg($file, $target, $axis, $size, $quality),
            'png'  => self::png($file, $target, $axis, $size, $quality),
            default => [
                'error'    => 'unknown format: ' . $format,
                'cmd'      => '',
                'rotated'  => false,
                'rotateMs' => 0.0,
            ],
        };
    }

    /**
     * iwebp — тот же webp, но кодирует vipsthumbnail, опции в WEBP_VIPS. Разворот
     * по exif он делает сам, прохода через gd нет. Держим рядом с webp для сравнения.
     */
    protected static function iwebp(string $file, string $target, string $axis, int $size, int $quality): array
    {
        return self::run([
            'vipsthumbnail',
            $file,
            '--size',
            $axis === 'x' ? $size . 'x' : 'x' . $size,
            '-o',
            $target . '[Q=' . $quality . ',' . self::WEBP_VIPS . ']',
        ]);
    }

    /**
     * webp — cwebp. Ноль в `-resize` означает «посчитай сам по пропорциям».
     *
     * Повёрнутый снимок разворачиваем до cwebp: сам он не крутит. Метаданные не
     * переносим — блок exif тащит встроенную превьюшку, плюс 55 КБ на файл.
     */
    protected static function webp(string $file, string $target, string $axis, int $size, int $quality): array
    {
        $start    = microtime(true);
        $upright  = self::upright($file);
        $rotateMs = $upright === null ? 0.0 : round((microtime(true) - $start) * 1000, 1);

        try {
            // array_merge, а не `+`: при объединении через `+` побеждает левая
            // сторона, и умолчания из run() затирали бы эти два ключа
            return array_merge(self::run([
                'cwebp',
                '-preset',
                'photo',
                '-resize',
                $axis === 'x' ? (string) $size : '0',
                $axis === 'x' ? '0' : (string) $size,
                '-m',
                '6',
                '-af',
                '-mt',
                '-sharpness',
                '0',
                '-sharp_yuv',
                '-q',
                (string) $quality,
                $upright ?? $file,
                '-o',
                $target,
            ]), [
                'rotated'  => $upright !== null,
                'rotateMs' => $rotateMs,
            ]);
        } finally {
            if ($upright !== null) {
                @unlink($upright);
            }
        }
    }

    /**
     * Развёрнутая копия во временном файле или null, если разворачивать нечего.
     *
     * Через gd, поэтому картинка целиком лежит в памяти: 3456 × 3456 это ~48 МБ.
     * Пишем в сам файл от tempnam, без расширения — иначе в /tmp остаётся пустышка,
     * а формат cwebp определяет по содержимому.
     */
    protected static function upright(string $file): ?string
    {
        $orientation = self::orientation($file);

        if ($orientation <= 1 || ! function_exists('imagecreatefromjpeg')) {
            return null;
        }

        $image = @imagecreatefromjpeg($file);

        if ($image === false) {
            return null;
        }

        // imagerotate крутит против часовой стрелки
        match ($orientation) {
            2 => imageflip($image, IMG_FLIP_HORIZONTAL),
            3 => $image = imagerotate($image, 180, 0),
            4 => imageflip($image, IMG_FLIP_VERTICAL),
            5 => [$image = imagerotate($image, 270, 0), imageflip($image, IMG_FLIP_HORIZONTAL)],
            6 => $image = imagerotate($image, 270, 0),
            7 => [$image = imagerotate($image, 90, 0), imageflip($image, IMG_FLIP_HORIZONTAL)],
            8 => $image = imagerotate($image, 90, 0),
            default => null,
        };

        $temp = tempnam(sys_get_temp_dir(), 'mpro_');

        imagejpeg($image, $temp, 95);
        imagedestroy($image);

        return $temp;
    }

    /**
     * avif — vipsthumbnail. Размер как `800x` или `x800`: пустая сторона означает
     * «без ограничения». Опции формата идут в скобках в имени выходного файла.
     * Разворот по exif vipsthumbnail делает сам.
     */
    protected static function avif(string $file, string $target, string $axis, int $size, int $quality): array
    {
        return self::run([
            'vipsthumbnail',
            $file,
            '--size',
            $axis === 'x' ? $size . 'x' : 'x' . $size,
            '-o',
            $target . '[Q=' . $quality . ',effort=4,subsample-mode=on]',
        ]);
    }

    /** jpg — vipsthumbnail. */
    protected static function jpg(string $file, string $target, string $axis, int $size, int $quality): array
    {
        return self::run([
            'vipsthumbnail',
            $file,
            '--size',
            $axis === 'x' ? $size . 'x' : 'x' . $size,
            '-o',
            $target . '[Q=' . $quality . ',optimize-coding=true]',
        ]);
    }

    /** png — vipsthumbnail. У png качества нет, там уровень сжатия. */
    protected static function png(string $file, string $target, string $axis, int $size, int $quality): array
    {
        return self::run([
            'vipsthumbnail',
            $file,
            '--size',
            $axis === 'x' ? $size . 'x' : 'x' . $size,
            '-o',
            $target . '[compression=' . $quality . ']',
        ]);
    }

    /** Поворот из exif. 1 и отсутствие тега означают «уже правильно». */
    public static function orientation(string $file): int
    {
        if (! function_exists('exif_read_data') || @exif_imagetype($file) !== IMAGETYPE_JPEG) {
            return 1;
        }

        return (int) (@exif_read_data($file)['Orientation'] ?? 1);
    }

    /**
     * Запуск. Аргументы экранируются по одному: в имени файла может быть пробел
     * или кавычка. Ошибку берём по коду возврата — «vips: not found» приходит
     * текстом и выглядит как удачный ответ.
     *
     * `rotated` и `rotateMs` пустые, чтобы форма ответа была одна на все форматы;
     * перебивает их только webp.
     */
    protected static function run(array $args): array
    {
        $command = implode(' ', array_map('escapeshellarg', $args));

        $lines = [];
        $code  = 1;

        @exec($command . ' 2>&1', $lines, $code);

        return [
            'error'    => $code === 0 ? '' : (trim(implode(' ', $lines)) ?: 'exit code ' . $code),
            'cmd'      => $command,
            'rotated'  => false,
            'rotateMs' => 0.0,
        ];
    }
}
