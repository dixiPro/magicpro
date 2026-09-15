<?php

namespace MagicProSrc\Image;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use MagicProSrc\Config\MagicGlobals;

/**
 * Задание на ресайз: одно уменьшение одной картинки.
 *
 * Всё, что вычисляется, вычисляется один раз и лежит в полях. Между методами
 * ходит сам объект, а не шесть аргументов.
 *
 * Снаружи четыре статических входа: make() — сделать, clear() — снести кеш
 * одного исходника, clearAll() — снести весь кеш, cleanup() — убрать сироты.
 * Исключений не бросает: не вышло — пустой path и текст в errorMsg. Заглушку
 * ресайзер не подставляет, что показывать вместо картинки решает блейд.
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

        // «исключений не бросает» — про весь путь, а не про одно ожидание
        // замка: сбой кеша, диска или прав тоже уходит в errorMsg, картинка
        // не роняет страницу
        try {
            $job->init($axis, $size, $format, $quality);

            if ($job->valid() && ! $job->fresh()) {
                $job->run();
            }
        } catch (\Throwable $e) {
            $job->errorMsg = $e->getMessage() ?: get_class($e);
        }

        return $job->toArray();
    }

    /**
     * Снести все производные исходника, любых размеров и форматов.
     *
     * Каталог читается целиком, а не через `glob()`: имя файла попадало бы в
     * шаблон как есть, и `*`, `?` или скобки в нём превращались бы в
     * метасимволы — свои производные не нашлись бы, чужие попали под удаление.
     */
    public static function clear(string $file): int
    {
        $job   = new self($file);
        $dir   = storage_path('app/public/' . $job->dir);
        $count = 0;

        foreach (@scandir($dir) ?: [] as $name) {
            if (! str_starts_with($name, $job->base . '_')) {
                continue;
            }

            // photo_x800.webp: за именем идёт ось, размер и расширение
            if (! preg_match('/^_[xy]\d+\./', substr($name, strlen($job->base)))) {
                continue;
            }

            $count += (int) @unlink($dir . '/' . $name);
        }

        return $count;
    }

    /**
     * Снести весь кеш ресайза: файлы и опустевшие подпапки. Сама папка кеша
     * остаётся — её создаёт установка, а не ресайз.
     *
     * Потеря не страшна: каждый файл делается заново при первом же обращении.
     */
    public static function clearAll(): array
    {
        $root  = storage_path('app/public/' . self::DIR);
        $files = 0;
        $bytes = 0;

        if (! is_dir($root)) {
            return ['files' => $files, 'bytes' => $bytes];
        }

        $walk = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($walk as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());

                continue;
            }

            $size = (int) @filesize($item->getPathname());

            if (@unlink($item->getPathname())) {
                $files++;
                $bytes += $size;
            }
        }

        return ['files' => $files, 'bytes' => $bytes];
    }

    /**
     * Remove the derivatives whose source is gone.
     *
     * Nothing is written down about what was made from what, and nothing needs
     * to be: the cache path names the source itself — letter of the root, the
     * directory under it, and the file name up to the size suffix. So «is the
     * original still there» is answered by looking at the disk instead of by a
     * second copy of the truth, which would be the first thing to go stale.
     *
     * The extension of the source is not in the cache name, so a directory is
     * read once and names are compared without extensions: `photo.jpg` and
     * `photo.png` both keep `photo_x800.avif` alive. A deliberate bias towards
     * keeping: a stale file in the cache costs less than a deleted needed one.
     *
     * Files under `x/` — source outside the project, folder named by a hash —
     * cannot be traced back and are never deleted; they go to `skipped`.
     */
    public static function cleanup(): array
    {
        $root = storage_path('app/public/' . self::DIR);
        $out  = ['files' => 0, 'bytes' => 0, 'kept' => 0, 'skipped' => 0];

        if (! is_dir($root)) {
            return $out;
        }

        $walk = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        // source directory => the names without extensions lying in it. The
        // cache repeats the tree of the sources, so one scandir serves every
        // derivative of one folder — and there are as many as sizes asked for
        $seen = [];

        foreach ($walk as $item) {
            $file = $item->getPathname();

            if ($item->isDir()) {
                // CHILD_FIRST: whatever could go from here has already gone,
                // so an emptied folder leaves in the same pass. rmdir will not
                // touch a folder that still holds anything
                @rmdir($file);

                continue;
            }

            $back = self::sourceBase(substr($file, strlen($root) + 1));

            if ($back === null) {
                $out['skipped']++;

                continue;
            }

            [$dir, $name] = $back;

            $seen[$dir] ??= self::baseNames($dir);

            if (isset($seen[$dir][$name])) {
                $out['kept']++;

                continue;
            }

            $size = (int) @filesize($file);

            if (@unlink($file)) {
                $out['files']++;
                $out['bytes'] += $size;
            }
        }

        return $out;
    }

    /**
     * The cache path back into its source: `p/storage/magicFeed/11/photo_x800.avif`
     * gives the folder `public_path('storage/magicFeed/11')` and the name `photo`.
     *
     * Null means the path says nothing about a source: an `x` folder, a letter
     * we do not know, or a name without a size suffix — something the resizer
     * did not put here.
     */
    protected static function sourceBase(string $rel): ?array
    {
        $parts  = explode('/', $rel);
        $file   = array_pop($parts);
        $letter = array_shift($parts) ?? '';

        $roots = ['p' => public_path(), 's' => storage_path(), 'b' => base_path()];

        if (! isset($roots[$letter])) {
            return null;
        }

        // The greedy group takes the last suffix: a source called
        // `photo_x800.jpg` makes `photo_x800_x400.avif`, and ours is the second
        // one. A tail of two extensions is `iwebp`: `photo_x800.iwebp.webp`
        if (! preg_match('/^(.+)_[xy]\d+\.[A-Za-z0-9]+(?:\.[A-Za-z0-9]+)?$/', $file, $m)) {
            return null;
        }

        $dir = rtrim($roots[$letter], '/');

        if ($parts) {
            $dir .= '/' . implode('/', $parts);
        }

        return [$dir, $m[1]];
    }

    /** File names of a directory without extensions. No directory — empty. */
    protected static function baseNames(string $dir): array
    {
        $names = [];

        foreach (@scandir($dir) ?: [] as $entry) {
            if ($entry !== '.' && $entry !== '..') {
                $names[pathinfo($entry, PATHINFO_FILENAME)] = true;
            }
        }

        return $names;
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
        // @: файл могли снести между is_file и filemtime — это «не свежий»,
        // а не ошибка
        return is_file($this->target) && @filemtime($this->target) >= @filemtime($this->source);
    }

    /**
     * Кодирование под замком: два запроса могут попросить один и тот же ещё не
     * сделанный файл. Пока ждали очереди, его мог сделать сосед.
     *
     * Утилита пишет во временный файл рядом с целевым, и на место он переезжает
     * `rename()` только целым. Раньше она писала сразу в целевой: упала на
     * середине — обрывок оставался, следующий запрос находил его свежим по
     * времени и отдавал с нулевыми размерами. Теперь на месте либо старое, либо
     * новое целиком: `rename()` в пределах каталога атомарен.
     *
     * Поэтому замок только экономит работу — два запроса не кодируют одно и то
     * же дважды. Испортить файл друг другу они уже не могут, и кеш, который не
     * даёт замка, ресайз не останавливает.
     */
    public function run(): void
    {
        $lock = null;

        try {
            $lock = Cache::lock($this->lockKey, 30);
            $lock->block(5);
        } catch (LockTimeoutException) {
            $this->errorMsg = 'busy: ' . basename($this->target);

            return;
        } catch (\Throwable) {
            $lock = null;
        }

        $temp = '';

        try {
            if ($this->fresh()) {
                return;
            }

            $dir = dirname($this->target);

            if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
                throw new \RuntimeException('cannot create the cache folder: ' . dirname($this->path));
            }

            // имя кончается тем же расширением: vipsthumbnail выбирает формат
            // по нему. Точка в начале прячет файл из списков, а случайная
            // вставка разводит два параллельных запроса. Остался после
            // убитого процесса — его уберёт cleanup(), исходника у него нет
            $temp = $dir . '/.tmp-' . bin2hex(random_bytes(4)) . '-' . basename($this->target);

            $made = ImageEncoder::make(
                $this->source,
                $temp,
                $this->axis,
                $this->size,
                $this->format,
                $this->quality
            );

            // в диагностике — настоящее имя, а не временное
            $this->cmd      = str_replace($temp, $this->target, $made['cmd']);
            $this->rotated  = $made['rotated'];
            $this->rotateMs = $made['rotateMs'];
            $this->errorMsg = $made['error'];

            if ($this->errorMsg === '' && (int) @filesize($temp) === 0) {
                $this->errorMsg = 'the encoder wrote nothing: ' . basename($this->target);
            }

            if ($this->errorMsg === '' && ! @rename($temp, $this->target)) {
                $this->errorMsg = 'cannot put the result in place: ' . basename($this->target);
            }
        } finally {
            if ($temp !== '' && is_file($temp)) {
                @unlink($temp);
            }

            try {
                $lock?->release();
            } catch (\Throwable) {
                // не отпустился — уйдёт сам через 30 секунд
            }
        }
    }

    /**
     * Ответ. Размеры читаются из готового файла: вторую сторону считала утилита.
     * При ошибке `path` пустой, а `width`, `height` и `size` нулевые: заглушку
     * ресайзер не подставляет, что показывать вместо картинки решает блейд.
     */
    public function toArray(): array
    {
        if ($this->errorMsg === '') {
            [$this->width, $this->height] = @getimagesize($this->target) ?: [0, 0];

            $this->bytes = (int) @filesize($this->target);
        }

        return [
            // Путь от public, со слэшем и со storage: он же в src, он же
            // обратно в хелперы через public_path(). Внутреннее $this->path
            // считается от корня диска и наружу не идёт.
            //
            // Не вышло — пусто. Причина в errorMsg.
            'path'     => $this->errorMsg === '' ? $this->url : '',
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
            $root = rtrim($root, '/');

            // сам корень тоже его корень: файл, лежащий прямо в public, иначе
            // не проходил проверку на префикс `public/` и уезжал в ветку b
            if ($dir === $root) {
                return $letter;
            }

            if (str_starts_with($dir, $root . '/')) {
                return trim($letter . '/' . substr($dir, strlen($root) + 1), '/');
            }
        }

        return 'x/' . substr(md5($dir), 0, 8);
    }

    /**
     * У каждого формата своя настройка качества и своя шкала.
     *
     * Запасное значение тоже своё: у png это уровень сжатия 0–9, и общая
     * подстановка «82 на всех» давала бы ему число вне шкалы, стоит ключу
     * пропасть из настроек.
     */
    public static function defaultQuality(string $format): int
    {
        [$key, $fallback] = match ($format) {
            'avif' => ['AVIF_DEF_QUALITY', 50],
            'jpg'  => ['JPG_DEF_QUALITY', 70],
            'png'  => ['PNG_COMPRESSION', 6],
            default => ['WEBP_DEF_QUALITY', 82],
        };

        return (int) self::setting($key, $fallback);
    }

    /** Настройка из группы RESIZE. Ключа ещё нет в файле — берём умолчание. */
    public static function setting(string $key, mixed $default): mixed
    {
        return MagicGlobals::$INI['RESIZE'][$key] ?? $default;
    }
}
