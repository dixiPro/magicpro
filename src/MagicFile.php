<?php

namespace MagicProSrc;

use Illuminate\Support\Facades\File;
use RuntimeException;

class MagicFile
{
    protected string $base = '';   // base/storage/public/или пусто
    protected string $dir  = '';   // директория или пусто
    protected string $name = '';   // имя файла (обязательно)
    protected string $ext  = '';   // расширение или пусто

    // не доделана
    public static function saveToBasePathFile($filename, $content): void
    {
        $filename = base_path($filename);
        self::saveToFile($filename, $content);
        return;
    }

    public static function saveToFile($filename, $content): void
    {
        $dir = dirname($filename);

        if (!is_dir($dir)) {
            if (false === @mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new RuntimeException(sprintf('Unable to create directory "%s".', $dir));
            }
        }
        if (false === @file_put_contents($filename, $content)) {
            throw new RuntimeException(sprintf('Failed to write file "%s".', $filename));
        }
        clearstatcache(true, $filename);
        return;
    }

    public static function make(): self
    {
        return new self();
    }

    public function base(): self
    {
        $this->base = base_path();
        return $this;
    }

    public function storage(): self
    {
        $this->base = storage_path();
        return $this;
    }

    public function public(): self
    {
        $this->base = public_path();
        return $this;
    }

    public function dir(string $dir): self
    {
        $this->dir = trim($dir, '/');
        return $this;
    }

    public function name(string $name): self
    {
        $name = trim($name, '/');

        // если имя с расширением
        if (str_contains($name, '.')) {
            [$n, $e] = explode('.', $name, 2);
            $this->name = $n;
            $this->ext = $e;
        } else {
            $this->name = $name;
        }

        return $this;
    }

    public function ext(string $ext): self
    {
        $this->ext = trim($ext, '. ');
        return $this;
    }

    protected function buildPath(): string
    {
        if ($this->name === '') {
            throw new \Exception("Require filename");
        }

        $parts = [];

        if ($this->base !== '') {
            $parts[] = rtrim($this->base, '/');
        }

        if ($this->dir !== '') {
            $parts[] = trim($this->dir, '/');
        }

        $file = $this->name;
        if ($this->ext !== '') {
            $file .= '.' . $this->ext;
        }

        $parts[] = $file;

        // собрать и нормализовать
        return preg_replace('#/+#', '/', '/' . implode('/', $parts));
    }

    public function put(string $content)
    {
        $path = $this->buildPath();
        $dir = dirname($path);

        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0777, true, true);
        }

        // Пишем файл
        $res = File::put($path, $content);

        // Сбрасываем кеши
        clearstatcache(true, $path);
        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($path, true);
        }

        return $res;
    }
}
