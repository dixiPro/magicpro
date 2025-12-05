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

    private function checkPath(string $name): void
    {
        // убираем две точки всякие выкрутассы
        if (strpos($name, '..') !== false) {
            throw new \RuntimeException("Недопустимое '..' в $name");
        }

        // убираем две точки всякие выкрутассы
        if (strpos($name, '//') !== false) {
            throw new \RuntimeException("Недопустимое '//' в $name");
        }

        // начальная директория
        $startDir = public_path(FILES_JS_UPLOAD) . "/";

        if (!str_starts_with($name, $startDir)) {
            throw new \RuntimeException("Запрещён доступ вне $startDir");
        }
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
        $basePath = public_path($request->string('path')->toString());
        $this->checkPath($basePath);

        if (!is_dir($basePath)) {
            throw new \InvalidArgumentException("Директория '{$basePath}' не найдена");
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

        usort(
            $dirs,
            fn($a, $b) => (ctype_alnum($a['name'][0]) <=> ctype_alnum($b['name'][0]))
                ?: strnatcasecmp($a['name'], $b['name'])
        );
        usort(
            $files,
            fn($a, $b) => (ctype_alnum($a['name'][0]) <=> ctype_alnum($b['name'][0]))
                ?: strnatcasecmp($a['name'], $b['name'])
        );

        return array_merge($dirs, $files);
    }

    // ==================================
    // 📁 Создание папки (без права выполнения)
    private function mkdir(Request $request): array
    {

        $folderName = public_path(trim($request->string('folderName')->toString()));

        $this->checkPath($folderName);

        if (File::exists($folderName)) {
            throw new \RuntimeException("Папка '{$folderName}' уже существует");
        }

        if (!mkdir($folderName, 0755, true)) {
            throw new \RuntimeException("Не удалось создать папку '{$folderName}'");
        }

        return ['created' => $folderName];
    }

    // ==================================
    // ⬆️ Потоковая загрузка файла без X-заголовков
    private function uploadBin(Request $request): array
    {
        $basePath = public_path($request->string('path')->toString());
        $this->checkPath($basePath);

        if (!is_dir($basePath)) {
            throw new \InvalidArgumentException("Путь '{$basePath}' не существует");
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
        $basePath = public_path($request->string('path')->toString());
        $this->checkPath($basePath);

        if (!is_dir($basePath)) {
            throw new \InvalidArgumentException("Путь '{$basePath}' не существует");
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
        $deleteFile = public_path($request->string('deleteFile')->toString());
        $this->checkPath($deleteFile);

        if (!File::exists($deleteFile)) {
            throw new \RuntimeException("Элемент '{$deleteFile}' не найден");
        }

        File::isDirectory($deleteFile)
            ? File::deleteDirectory($deleteFile)
            : File::delete($deleteFile);

        return ['deleted' => $deleteFile];
    }

    // ==================================
    // ✏️ Переименование файла/папки
    private function rename(Request $request): array
    {

        $oldName = public_path($request->string('oldName')->toString());
        $newName = public_path($request->string('newName')->toString());

        $this->checkPath($oldName);
        $this->checkPath($newName);

        if (!File::exists($oldName)) {
            throw new \RuntimeException("Элемент '{$oldName}' не найден");
        }

        if (File::exists($newName)) {
            throw new \RuntimeException("Элемент '{$newName}' уже существует");
        }

        rename($oldName, $newName);

        return ['renamed' => [$oldName => $newName]];
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
