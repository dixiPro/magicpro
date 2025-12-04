<?php

namespace MagicProAdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use MagicProSrc\Config\MagicGlobals; // Глобальные константы

// use SplTempFileObject;

class API_FileManagerPostController extends Controller
{
    /*
    📁 Базовая директория для всех файловых операций
    BASE_DIR = 'design';
    Возвращает абсолютный путь внутри public/design,
    гарантируя, что нельзя выйти за её пределы.
    */

    private function safePath(string $relative): string
    {
        // Убираем начальные/конечные слэши
        $relative = trim($relative, '/\\');

        // Если пусто — просто /design
        if ($relative === '' || $relative === FILES_JS_UPLOAD) {
            return public_path(FILES_JS_UPLOAD);
        }

        // Если начинается не с design — ошибка
        if (!str_starts_with($relative, FILES_JS_UPLOAD . '/')) {
            throw new \RuntimeException("Попытка доступа за пределы директории /" . FILES_JS_UPLOAD);
        }

        // Собираем путь без realpath
        $basePath = public_path($relative);

        // Проверяем что путь не вылез наружу (без зависания от прав)
        $normalizedBase = str_replace('\\', '/', public_path(FILES_JS_UPLOAD));
        $normalizedFull = str_replace('\\', '/', $basePath);

        if (strpos($normalizedFull, $normalizedBase) !== 0) {
            throw new \RuntimeException("Попытка обхода директории /" . FILES_JS_UPLOAD);
        }

        return $basePath;
    }


    public function handle(Request $request): JsonResponse
    {
        try {
            $methods = [
                'start' => ['name' => 'start'],
                'dirList' => ['name' => 'dirList'],
                'mkdir'   => ['name' => 'mkdir'],
                'upload'  => ['name' => 'upload'],
                'uploadBin'  => ['name' => 'uploadBin'],
                'delete'  => ['name' => 'delete'],
                'rename'  => ['name' => 'rename'],
                'loadFile'  => ['name' => 'loadFile'],
                'saveFile'  => ['name' => 'saveFile'],
            ];

            $command = $request->string('command')->toString();

            if (!array_key_exists($command, $methods)) {
                throw new \InvalidArgumentException("Unknown command '{$command}'");
            }

            $methodName = $methods[$command]['name'];
            if (!method_exists($this, $methodName)) {
                throw new \BadMethodCallException("Method {$methodName} not found");
            }

            $data = $this->{$methodName}($request);

            return response()->json([
                'status'  => true,
                'data'    => $data,
                'request' => $request->all(),
            ]);
        } catch (\Throwable $th) {
            $msg = $th->getMessage();
            if ($th->getFile()) $msg .= ' in ' . $th->getFile();
            if ($th->getLine()) $msg .= ' on line ' . $th->getLine();

            return response()->json([
                'status'  => false,
                'errorMsg' => $msg,
                'request' => $request->all(),
            ]);
        }
    }


    // ==================================


    private function checkFileInPublicStorageDir(string $fileName): void
    {
        $startPath = realpath(public_path(MagicGlobals::$magicStorageDir));
        $fileName = realpath($fileName);

        // Если файла нет или не удалось определить путь — исключение
        if (!$fileName) {
            throw new \Exception("Файл не найден: $fileName");
        }

        if (is_dir($fileName)) {
            throw new \Exception("Это директория " . $fileName);
        }


        // Проверка принадлежности директории
        if (!str_starts_with($fileName, $startPath . DIRECTORY_SEPARATOR)) {
            throw new \Exception("Файл вне разрешённой директории");
        }
    }

    private function loadFile(Request $request): array
    {

        $fileName = public_path($request->input('fileName'));
        $this->checkFileInPublicStorageDir($fileName);
        $this->validateEditExtension($fileName);

        return ['fileData' => file_get_contents($fileName)];
    }

    private function saveFile(Request $request): array
    {

        $fileName = public_path($request->input('fileName'));
        $this->checkFileInPublicStorageDir($fileName);
        $this->validateEditExtension($fileName);
        $fileData = $request->input('fileData');

        // Пишем файл
        $status = @file_put_contents($fileName, $fileData);

        // Если запись не удалась
        if ($status === false) {
            $e = error_get_last();
            throw new \Exception("Ошибка сохранения: $fileName " . ($e['message'] ?? ''));
        }

        return ['status' => 1];
    }



    // Старт возвращает стартовую директорию
    private function start(Request $request): array
    {

        $path = Str::start(FILES_JS_UPLOAD, '/');
        $path = Str::finish($path, '/');

        return ['startDirectory' => $path];
    }

    // ==================================
    // 📂 Список директорий и файлов
    private function dirList(Request $request): array
    {
        $relativePath = $request->string('path')->toString();
        $basePath = $this->safePath($relativePath);

        if (!is_dir($basePath)) {
            throw new \InvalidArgumentException("Директория '{$relativePath}' не найдена");
        }

        $dirs = [];
        $files = [];

        foreach (scandir($basePath) as $file) {
            if ($file === '.' || $file === '..') continue;

            $full  = $basePath . DIRECTORY_SEPARATOR . $file;
            $isDir = is_dir($full);
            $mime  = $isDir ? 'directory' : (mime_content_type($full) ?: 'application/octet-stream');

            $item = [
                'name'    => $file,
                'type'    => $isDir ? 'dir' : 'file',
                'mime'    => $mime,
                'size'    => $isDir ? null : filesize($full),
                'mtime'   => date('Y-m-d H:i:s', filemtime($full)),
                'isImage' => false,
            ];

            if (!$isDir && str_starts_with($mime, 'image/')) {
                $dim = @getimagesize($full);
                if ($dim !== false) {
                    $item['width']   = $dim[0];
                    $item['height']  = $dim[1];
                    $item['isImage'] = true;
                }
            }

            if ($isDir) {
                $dirs[] = $item;
            } else {
                $files[] = $item;
            }
        }

        usort($dirs, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));
        usort($files, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));

        return array_merge($dirs, $files);
    }

    // ==================================
    // 📁 Создание папки (без права выполнения)
    private function mkdir(Request $request): array
    {
        $relativePath = $request->string('path')->toString();
        $name = trim($request->string('name')->toString());

        if ($name === '') {
            throw new \InvalidArgumentException('Имя папки не может быть пустым');
        }

        $dir = $this->safePath($relativePath . DIRECTORY_SEPARATOR . $name);

        if (File::exists($dir)) {
            throw new \RuntimeException("Папка '{$name}' уже существует");
        }

        if (!mkdir($dir, 0755, true)) {
            throw new \RuntimeException("Не удалось создать папку '{$name}'");
        }

        return ['created' => $name];
    }

    // ==================================
    // ⬆️ Потоковая загрузка файла без X-заголовков
    private function uploadBin(Request $request): array
    {
        $relativePath = $request->string('path')->toString();
        $basePath = $this->safePath($relativePath);

        if (!is_dir($basePath)) {
            throw new \InvalidArgumentException("Путь '{$relativePath}' не существует");
        }

        $fileName = $request->string('filename')->toString() ?: 'upload.bin';

        $this->validateExtension($fileName);

        $fullPath = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;

        $stream = fopen('php://input', 'rb');
        $dest   = fopen($fullPath, 'wb');

        if (!$stream || !$dest) {
            throw new \RuntimeException('Ошибка открытия потоков');
        }


        while (!feof($stream)) {
            $chunk = fread($stream, 8192);
            if ($chunk === false) break;
            fwrite($dest, $chunk);
        }

        fclose($stream);
        fclose($dest);

        chmod($fullPath, 0644);

        $mime  = mime_content_type($fullPath) ?: 'application/octet-stream';
        $size  = filesize($fullPath);
        $mtime = date('U', filemtime($fullPath));

        $item = [
            'name'  => $fileName,
            'type'  => 'file',
            'mime'  => $mime,
            'size'  => $size,
            'mtime' => date('Y-m-d H:i:s', filemtime($fullPath)),
            'date'  => date('U', filemtime($fullPath)),
        ];

        if (str_starts_with($mime, 'image/')) {
            $dim = @getimagesize($fullPath);
            if ($dim !== false) {
                $item['width']   = $dim[0];
                $item['height']  = $dim[1];
                $item['isImage'] = true;
            }
        }

        return $item;
    }

    // ==================================
    // ⬆️ Загрузка файла (Base64)
    private function upload(Request $request): array
    {
        $relativePath = $request->string('path')->toString();
        $basePath = $this->safePath($relativePath);

        if (!is_dir($basePath)) {
            throw new \InvalidArgumentException("Путь '{$relativePath}' не существует");
        }

        $base64   = $request->input('file');
        $fileName = $request->input('filename') ?? 'upload.bin';

        if (!$base64) {
            throw new \InvalidArgumentException('Файл не передан');
        }
        $this->validateExtension($fileName);

        $decoded = base64_decode($base64);
        if ($decoded === false) {
            throw new \InvalidArgumentException('Ошибка декодирования файла');
        }

        $fullPath = rtrim($basePath, '/') . '/' . $fileName;

        file_put_contents($fullPath, $decoded);
        chmod($fullPath, 0644);

        $mime  = mime_content_type($fullPath) ?: 'application/octet-stream';
        $size  = filesize($fullPath);
        $mtime = date('Y-m-d H:i:s', filemtime($fullPath));
        $date  = date('U', filemtime($fullPath));

        $item = [
            'name'  => $fileName,
            'type'  => 'file',
            'mime'  => $mime,
            'size'  => $size,
            'mtime' => $mtime,
            'date'  => $date,
        ];

        if (str_starts_with($mime, 'image/')) {
            $dim = @getimagesize($fullPath);
            if ($dim !== false) {
                $item['width']   = $dim[0];
                $item['height']  = $dim[1];
                $item['isImage'] = true;
            }
        }

        return $item;
    }

    // ==================================
    // ❌ Удаление файла или папки
    private function delete(Request $request): array
    {
        $relativePath = $request->string('path')->toString();
        $name = $request->string('name')->toString();
        $target = $this->safePath($relativePath . DIRECTORY_SEPARATOR . $name);

        if (!File::exists($target)) {
            throw new \RuntimeException("Элемент '{$name}' не найден");
        }

        File::isDirectory($target)
            ? File::deleteDirectory($target)
            : File::delete($target);

        return ['deleted' => $name];
    }

    // ==================================
    // ✏️ Переименование файла/папки
    private function rename(Request $request): array
    {
        $relativePath = $request->string('path')->toString();
        $old = $request->string('old')->toString();
        $new = $request->string('new')->toString();

        $basePath = $this->safePath($relativePath);
        $oldFull = $basePath . DIRECTORY_SEPARATOR . $old;
        $newFull = $basePath . DIRECTORY_SEPARATOR . $new;

        if (!File::exists($oldFull)) {
            throw new \RuntimeException("Элемент '{$old}' не найден");
        }

        if (File::exists($newFull)) {
            throw new \RuntimeException("Элемент '{$new}' уже существует");
        }

        rename($oldFull, $newFull);

        return ['renamed' => [$old => $new]];
    }

    // ==================================
    // ⬆️ Проверка расширения загрузки
    private function validateExtension(string $fileName): void
    {
        $allowed = [
            // Изображения
            'jpg',
            'jpeg',
            'jpe',
            'jfif',
            'png',
            'gif',
            'webp',
            'svg',
            'ico',
            'psd',
            'nef',
            // Документы
            'pdf',
            'doc',
            'docx',
            'xls',
            'xlsx',
            'ppt',
            'pptx',
            'txt',
            'rtf',
            'odt',
            'ods',
            'odp',
            // Аудио
            'mp3',
            'wav',
            'ogg',
            'aac',
            'flac',
            // Видео
            'mp4',
            'avi',
            'mkv',
            'mov',
            'wmv',
            'webm',
            // Архивы
            'zip',
            'rar',
            '7z',
            'tar',
            'gz',
            'tar.gz',
            // Данные
            'csv',
            'css',
            'js',
            'json',
            'xml',
            'sql',
            'md',
            // Другое
            'ics',
            'vcf',
        ];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!$ext || !in_array($ext, $allowed, true)) {
            throw new \InvalidArgumentException("Недопустимое расширение файла: {$ext}");
        }
    }

    // ⬆️ Проверка расширения 
    private function validateEditExtension(string $fileName): void
    {
        $allowed = [
            'txt',
            'rtf',
            'csv',
            'css',
            'js',
            'json',
            'xml',
            'sql',
            'md',
        ];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!$ext || !in_array($ext, $allowed, true)) {
            throw new \InvalidArgumentException("Недопустимое расширение файла: {$ext}");
        }
    }
}
