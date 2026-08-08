<?php

namespace MagicProSrc\Image;

use Illuminate\Support\Facades\Cache;
use MagicProSrc\Config\MagicGlobals;

/**
 * Задание на ресайз: одно уменьшение одной картинки.
 *
 * Всё, что вычисляется, вычисляется один раз и лежит в полях. Между методами
 * ходит сам объект, а не шесть аргументов.
 *
 * Снаружи два статических входа: make() — сделать, clear() — снести кеш исходника.
 * Исключений не бросает: не вышло — заглушка и текст в errorMsg.
 */
class ImageJob
{
    /** Папка кеша внутри storage/app/public. */
    public const DIR = 'magic/images';

    // задание
    public string $source;
    public string $axis    = 'x';
    public int $size       = 0;
    public string $format  = '';
    public int $quality    = 0;

    // вычисленное
    public string $dir     = '';   // папка кеша, от корня диска
    public string $base    = '';   // имя исходника без расширения
    public string $target  = '';   // абсолютный путь результата
    public string $path    = '';   // он же от корня диска
    public string $url     = '';
    public string $lockKey = '';
    public float $start;

    // итог
    public int $width     = 0;
    public int $height    = 0;
    public int $bytes     = 0;
    public string $cmd    = '';
    public bool $rotated  = false;
    public float $rotateMs = 0.0;
    public string $errorMsg = '';

    /** Первый шаг: то, что известно по одному исходнику. */
    public function __construct(string $file)
    {
        $this->start  = microtime(true);
        $this->source = $file;
        $this->base   = pathinfo($file, PATHINFO_FILENAME);
        $this->dir    = self::DIR . '/' . $this->place($file);
    }

    /**
     * Уменьшить $file по стороне $axis ('x' — ширина, 'y' — высота) до $size.
     * Вторую сторону считает утилита, $format и $quality без значения берутся из
     * настроек.
     */
    public static function make(
        string $file,
        string $axis,
        int $size,
        ?string $format = null,
        ?int $quality = null
    ): array {
        $job = new self($file);

        $job->init($axis, $size, $format, $quality);

        if (! $job->valid() || $job->fresh()) {
            return $job->toArray();
        }

        $job->run();

        return $job->toArray();
    }

    /** Снести все производные исходника, любых размеров и форматов. */
    public static function clear(string $file): int
    {
        $job   = new self($file);
        $count = 0;

        foreach (glob(storage_path('app/public/' . $job->dir) . '/' . $job->base . '_*.*') ?: [] as $found) {
            $count += (int) @unlink($found);
        }

        return $count;
    }

    /** Второй шаг: то, что зависит от оси, размера и формата. */
    public function init(string $axis, int $size, ?string $format, ?int $quality): void
    {
        $this->axis    = $axis;
        $this->format  = $format ?? (string) self::setting('RESIZE_FORMAT', 'webp');
        $this->size    = max(1, min($size, (int) self::setting('MAX_RESIZE', 1000)));
        $this->quality = $quality ?? self::defaultQuality($this->format);

        // метка кодировщика остаётся в имени, а расширение должно быть настоящим:
        // photo_x800.iwebp.webp. Иначе сервер отдаст неизвестный тип, а два
        // кодировщика одного формата затрут файлы друг друга
        $name = $this->format === 'iwebp' ? 'iwebp.webp' : $this->format;

        $this->path    = $this->dir . '/' . $this->base . '_' . $this->axis . $this->size . '.' . $name;
        $this->target  = storage_path('app/public/' . $this->path);
        $this->url     = '/storage/' . $this->path;
        $this->lockKey = 'image:' . md5($this->target);
    }

    /** Исходник — картинка, формат — из тех, что умеем. */
    public function valid(): bool
    {
        if (! is_file($this->source) || @getimagesize($this->source) === false) {
            $this->errorMsg = 'not an image: ' . $this->source;
        } elseif (! in_array($this->format, ImageEncoder::FORMATS, true)) {
            $this->errorMsg = 'unknown format: ' . $this->format;
        }

        return $this->errorMsg === '';
    }

    /** Готовый файл есть и он новее исходника. */
    public function fresh(): bool
    {
        return is_file($this->target) && filemtime($this->target) >= filemtime($this->source);
    }

    /**
     * Кодирование под замком: два запроса могут попросить один и тот же ещё не
     * сделанный файл. Пока ждали очереди, его мог сделать сосед.
     */
    public function run(): void
    {
        $lock = Cache::lock($this->lockKey, 30);

        try {
            $lock->block(5);
        } catch (\Throwable $e) {
            $this->errorMsg = 'busy: ' . basename($this->target);

            return;
        }

        try {
            if ($this->fresh()) {
                return;
            }

            is_dir(dirname($this->target)) || mkdir(dirname($this->target), 0775, true);

            $made = ImageEncoder::make(
                $this->source,
                $this->target,
                $this->axis,
                $this->size,
                $this->format,
                $this->quality
            );

            $this->cmd      = $made['cmd'];
            $this->rotated  = $made['rotated'];
            $this->rotateMs = $made['rotateMs'];
            $this->errorMsg = $made['error'];
        } finally {
            $lock->release();
        }
    }

    /**
     * Ответ. Размеры читаются из готового файла: вторую сторону считала утилита.
     * При ошибке вместо картинки svg из настроек, data-uri прямо в url, — блейд
     * не меняется.
     */
    public function toArray(): array
    {
        if ($this->errorMsg === '') {
            [$this->width, $this->height] = @getimagesize($this->target) ?: [0, 0];

            $this->bytes = (int) @filesize($this->target);
        }

        return [
            'url' => $this->errorMsg === ''
                ? $this->url
                : 'data:image/svg+xml;base64,' . base64_encode((string) self::setting('IMAGE_ERROR', '')),
            'path'     => $this->errorMsg === '' ? $this->path : '',
            'width'    => $this->width,
            'height'   => $this->height,
            'size'     => $this->bytes,
            'ms'       => round((microtime(true) - $this->start) * 1000, 1),
            'rotated'  => $this->rotated,
            'rotateMs' => $this->rotateMs,
            'cmd'      => $this->cmd,   // пусто — значит отдали из кеша
            'errorMsg' => $this->errorMsg,
        ];
    }

    /**
     * Источник одной буквой плюс путь относительно него: `p` — public, `s` —
     * storage, `b` — корень проекта, `x` — чужой файл, тогда хеш каталога.
     * Абсолютный путь сервера в кеш не переносится.
     */
    protected function place(string $file): string
    {
        $dir = dirname($file);

        foreach (['p' => public_path(), 's' => storage_path(), 'b' => base_path()] as $letter => $root) {
            $root = rtrim($root, '/') . '/';

            if (str_starts_with($dir, $root)) {
                return trim($letter . '/' . substr($dir, strlen($root)), '/');
            }
        }

        return 'x/' . substr(md5($dir), 0, 8);
    }

    /** У каждого формата своя настройка качества и своя шкала. */
    public static function defaultQuality(string $format): int
    {
        $key = match ($format) {
            'avif' => 'AVIF_DEF_QUALITY',
            'jpg'  => 'JPG_DEF_QUALITY',
            'png'  => 'PNG_COMPRESSION',
            default => 'WEBP_DEF_QUALITY',
        };

        return (int) self::setting($key, 82);
    }

    /** Настройка из группы RESIZE. Ключа ещё нет в файле — берём умолчание. */
    public static function setting(string $key, mixed $default): mixed
    {
        return MagicGlobals::$INI['RESIZE'][$key] ?? $default;
    }
}
