<?php

namespace MagicProAdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use MagicProSrc\Config\MagicGlobals; // global constants
use MagicProSrc\MagicFile;


class API_FileManagerPostController extends Controller
{
    /*
    📁 base directory for all file operations, for example   BASE_DIR = '/design';
    returns an absolute path inside public/design,
    guaranteeing that it cannot escape its boundaries.
    */

    private function checkPath(string $name): void
    {
        // remove ".." and other tricks
        if (strpos($name, '..') !== false) {
            throw new \RuntimeException("invalid '..' in $name");
        }

        // remove ".." and other tricks
        if (strpos($name, '//') !== false) {
            throw new \RuntimeException("invalid '//' in $name");
        }

        // start directory
        $startDir = public_path(MagicGlobals::$INI['PUBLIC_UPLOAD_DIR']);

        if (!str_starts_with($name, $startDir)) {
            throw new \RuntimeException("access outside $startDir is forbidden");
        }
    }

    public function handle(Request $request): JsonResponse
    {
        try {
            $methods = [
                'start' => ['name' => 'start'],
                'dirList' => ['name' => 'dirList'],
                'mkdir'   => ['name' => 'mkdir'],
                'mkfile'   => ['name' => 'mkfile'],

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

        // if the file does not exist or the path could not be resolved — throw
        if (!$fileName) {
            throw new \Exception("file not found: $fileName");
        }

        if (is_dir($fileName)) {
            throw new \Exception("this is a directory " . $fileName);
        }


        // check allowed directory
        if (!str_starts_with($fileName, $startPath . DIRECTORY_SEPARATOR)) {
            throw new \Exception("file is outside the allowed directory");
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

        // write file
        $status = @file_put_contents($fileName, $fileData);

        // if write failed
        if ($status === false) {
            $e = error_get_last();
            throw new \Exception("save error: $fileName " . ($e['message'] ?? ''));
        }

        return ['status' => 1];
    }



    // start returns the start directory
    private function start(Request $request): array
    {

        $path = Str::start(MagicGlobals::$INI['PUBLIC_UPLOAD_DIR'], '/');
        $path = Str::finish($path, '/');

        return ['startDirectory' => $path];
    }

    // ==================================
    // 📂 directory and file list
    private function dirList(Request $request): array
    {
        $basePath = public_path($request->string('path')->toString());
        $this->checkPath($basePath);

        if (!is_dir($basePath)) {
            throw new \InvalidArgumentException("directory '{$basePath}' not found");
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
    // 📁 create folder (no execute permission)
    private function mkdir(Request $request): array
    {

        $folderName = public_path(trim($request->string('folderName')->toString()));

        $this->checkPath($folderName);

        if (File::exists($folderName)) {
            throw new \RuntimeException("folder '{$folderName}' already exists");
        }

        if (!mkdir($folderName, 0755, true)) {
            throw new \RuntimeException("failed to create folder '{$folderName}'");
        }

        return ['created' => $folderName];
    }

    // 📁 create folder (no execute permission)
    private function mkfile(Request $request): array
    {

        $fileName = public_path(trim($request->string('fileName')->toString()));

        $this->checkPath($fileName);

        if (File::exists($fileName)) {
            throw new \RuntimeException("folder '{$fileName}' already exists");
        }

        $this->validateEditExtension($fileName);

        MagicFile::saveToFile($fileName, '');


        return ['created' => $fileName];
    }

    // ==================================
    // ⬆️ streamed file upload without x-headers
    private function uploadBin(Request $request): array
    {
        $basePath = public_path($request->string('path')->toString());
        $this->checkPath($basePath);

        if (!is_dir($basePath)) {
            throw new \InvalidArgumentException("path '{$basePath}' does not exist");
        }

        $fileName = $request->string('filename')->toString() ?: 'upload.bin';

        $this->validateExtension($fileName);

        $fullPath = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;

        $stream = fopen('php://input', 'rb');
        $dest   = fopen($fullPath, 'wb');

        if (!$stream || !$dest) {
            throw new \RuntimeException('failed to open streams');
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
    // ⬆️ file upload (base64)
    private function upload(Request $request): array
    {
        $basePath = public_path($request->string('path')->toString());
        $this->checkPath($basePath);

        if (!is_dir($basePath)) {
            throw new \InvalidArgumentException("path '{$basePath}' does not exist");
        }

        $base64   = $request->input('file');
        $fileName = $request->input('filename') ?? 'upload.bin';

        if (!$base64) {
            throw new \InvalidArgumentException('file not provided');
        }
        $this->validateExtension($fileName);

        $decoded = base64_decode($base64);
        if ($decoded === false) {
            throw new \InvalidArgumentException('file decode error');
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
    // ❌ delete file or folder
    private function delete(Request $request): array
    {
        $deleteFile = public_path($request->string('deleteFile')->toString());
        $this->checkPath($deleteFile);

        if (!File::exists($deleteFile)) {
            throw new \RuntimeException("item '{$deleteFile}' not found");
        }

        File::isDirectory($deleteFile)
            ? File::deleteDirectory($deleteFile)
            : File::delete($deleteFile);

        return ['deleted' => $deleteFile];
    }

    // ==================================
    // ✏️ rename file/folder
    private function rename(Request $request): array
    {

        $oldName = public_path($request->string('oldName')->toString());
        $newName = public_path($request->string('newName')->toString());

        $this->checkPath($oldName);
        $this->checkPath($newName);

        if (!File::exists($oldName)) {
            throw new \RuntimeException("item '{$oldName}' not found");
        }

        if (File::exists($newName)) {
            throw new \RuntimeException("item '{$newName}' already exists");
        }

        rename($oldName, $newName);

        return ['renamed' => [$oldName => $newName]];
    }

    // ==================================
    // ⬆️ upload extension validation
    private function validateExtension(string $fileName): void
    {
        $allowed = [
            // images
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
            // documents
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
            // audio
            'mp3',
            'wav',
            'ogg',
            'aac',
            'flac',
            // video
            'mp4',
            'avi',
            'mkv',
            'mov',
            'wmv',
            'webm',
            // archives
            'zip',
            'rar',
            '7z',
            'tar',
            'gz',
            'tar.gz',
            // data
            'csv',
            'css',
            'js',
            'json',
            'xml',
            'sql',
            'md',
            // other
            'ics',
            'vcf',
        ];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!$ext || !in_array($ext, $allowed, true)) {
            throw new \InvalidArgumentException("invalid file extension: {$ext}");
        }
    }

    // ⬆️ edit extension validation
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
            throw new \InvalidArgumentException("invalid file extension: {$ext}");
        }
    }
}
