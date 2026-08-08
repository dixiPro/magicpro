<?php

namespace MagicProAdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use MagicProDatabaseModels\Article;
use MagicProDatabaseModels\MagicProUser;
use MagicProSrc\MagicLang;

use MagicProAdminControllers\API_ArticlesPostController;
use MagicProSrc\Config\MagicGlobals; // global constants

class AdminController extends Controller
{
    /** Сколько записей на странице списка статей. */
    private const ART_PER_PAGE = 50;

    /**
     * Утилиты, которыми режутся изображения: чем спросить версию и чем поставить.
     * Подсказка нужна там же, где красный крест, иначе он ничего не сообщает.
     */
    private const IMAGE_TOOLS = [
        'cwebp' => ['version' => 'cwebp -version', 'hint' => 'apt install webp'],
        'vips'  => ['version' => 'vips --version', 'hint' => 'apt install libvips-tools'],
    ];

    /** То же для php-расширений. */
    private const PHP_EXTENSIONS = [
        'gd'      => 'apt install php-gd && systemctl reload php-fpm',
        'imagick' => 'apt install php-imagick && systemctl reload php-fpm',
    ];

    /**
     * Главная админки: она же установка и она же проверка.
     *
     * Смысл в том, чтобы после composer install ничего не нужно было делать
     * руками: зашёл — чего нет, создалось, и тут же видно, что на месте.
     *
     * Проверки идут каждый раз, без кеша: кеш здесь сохраняет прошлое положение
     * дел и показывает его как нынешнее, а страница нужна ровно для обратного.
     */
    public function index()
    {
        return view('magicAdmin::index', [
            'steps' => $this->install(),
            'tools' => $this->imageTools(),
        ]);
    }

    /**
     * Шаги установки. Каждый сам смотрит, нужно ли что-то делать, и возвращает
     * строку отчёта: что проверяли, вышло или нет, и короткая приписка.
     *
     * Всё под общей блокировкой — иначе два администратора, зашедших
     * одновременно, вдвоём полезут создавать каталоги и копировать ассеты, и
     * второй увидит наполовину скопированную папку. Не дождались блокировки —
     * значит установку прямо сейчас делает сосед, и повторять её незачем.
     *
     * У каждого шага свой try: каталог загрузок не должен оставаться несозданным
     * из-за того, что упала перегенерация статей.
     */
    private function install(): array
    {
        $steps = ['installDataDir', 'installVendorPublic', 'installUploadDir', 'installStorageLink'];

        $lock = Cache::lock('magicpro:install', 60);

        try {
            $lock->block(5);
        } catch (LockTimeoutException $e) {
            return [];
        }

        $rows = [];

        try {
            foreach ($steps as $step) {
                try {
                    $rows[] = $this->{$step}();
                } catch (\Throwable $e) {
                    $rows[] = $this->stepFailed($step, $e);
                }
            }
        } finally {
            $lock->release();
        }

        return $rows;
    }

    /**
     * 1. Каталог данных: вьюхи и контроллеры статей.
     *
     * Раз каталога не было, генерированных файлов в нём тоже нет — поэтому сразу
     * за созданием идёт перегенерация всех статей.
     */
    private function installDataDir(): array
    {
        $title = MagicLang::getMsg('step_data_dir') . ': ' . MAGIC_DATA_DIR;

        if (is_dir(MAGIC_DATA_DIR)) {
            return $this->stepOk($title, MagicLang::getMsg('note_exists'));
        }

        if (!mkdir(MAGIC_DATA_DIR, 0775, true)) {
            throw new \Exception(MagicLang::getMsg('cannot_create_dir') . ': ' . MAGIC_DATA_DIR);
        }

        // права проверяем сразу: каталог мог создаться, а запись в него — нет
        $testFile = MAGIC_DATA_DIR . DIRECTORY_SEPARATOR . 'write_test.tmp';

        File::put($testFile, 'test');
        File::delete($testFile);

        $res = API_ArticlesPostController::run(['command' => 'regenerateAll']);

        if (!$res['status']) {
            throw new \Exception($res['errorMsg']);
        }

        return $this->stepOk(
            $title,
            MagicLang::getMsg('note_created') . ', ' . MagicLang::getMsg('regenerate_articles')
        );
    }

    /**
     * 2. Ассеты пакета в public.
     *
     * Копируем, когда каталога нет или в нём лежит другая версия: так обновление
     * пакета доезжает до public без ручной команды.
     */
    private function installVendorPublic(): array
    {
        $title = MagicLang::getMsg('step_vendor_public') . ': ' . VENDOR_PUBLIC;

        $versionFile = VENDOR_PUBLIC . '/version.txt';
        $installedVersion = is_file($versionFile) ? trim(file_get_contents($versionFile)) : null;

        if (is_dir(VENDOR_PUBLIC) && $installedVersion === MAGIC_VERSION) {
            return $this->stepOk($title, MAGIC_VERSION);
        }

        if (!is_dir(VENDOR_PUBLIC) && !mkdir(VENDOR_PUBLIC, 0775, true)) {
            throw new \Exception(MagicLang::getMsg('cannot_create_dir') . ': ' . VENDOR_PUBLIC);
        }

        File::copyDirectory(VENDOR_FROM, VENDOR_PUBLIC);
        file_put_contents($versionFile, MAGIC_VERSION);

        return $this->stepOk($title, MagicLang::getMsg('note_copied') . ' ' . MAGIC_VERSION);
    }

    /** 3. Каталог загрузок внутри public. */
    private function installUploadDir(): array
    {
        $startDir = public_path(MagicGlobals::$INI['PUBLIC_UPLOAD_DIR']);
        $title = MagicLang::getMsg('step_upload_dir') . ': ' . $startDir;

        if (is_dir($startDir)) {
            return $this->stepOk($title, MagicLang::getMsg('note_exists'));
        }

        if (!mkdir($startDir, 0775, true)) {
            throw new \Exception(MagicLang::getMsg('cannot_create_dir') . ': ' . $startDir);
        }

        return $this->stepOk($title, MagicLang::getMsg('note_created'));
    }

    /**
     * 4. Ссылка public/storage.
     *
     * Обычно её ставят руками через artisan storage:link. Делаем сами: без неё
     * не отдаются ни загруженные файлы, ни кеш производных изображений, а
     * ломается она молча — например при переносе проекта.
     */
    private function installStorageLink(): array
    {
        $link = public_path('storage');
        $target = storage_path('app/public');
        $title = MagicLang::getMsg('step_storage_link') . ': ' . $link;

        if (is_link($link) || is_dir($link)) {
            return $this->stepOk($title, MagicLang::getMsg('note_exists'));
        }

        if (!symlink($target, $link)) {
            throw new \Exception(MagicLang::getMsg('cannot_create_link') . ': ' . $link);
        }

        return $this->stepOk($title, MagicLang::getMsg('note_created'));
    }

    private function stepOk(string $title, string $note): array
    {
        return ['title' => $title, 'ok' => true, 'note' => $note, 'hint' => ''];
    }

    /** Ошибка шага: на экран строкой, в лог целиком — экран живёт до перезагрузки. */
    private function stepFailed(string $step, \Throwable $e): array
    {
        \MproHelper::addLog('install', [
            'step'    => $step,
            'message' => $e->getMessage(),
            'file'    => $e->getFile() . ':' . $e->getLine(),
        ]);

        return [
            'title' => $step,
            'ok'    => false,
            'note'  => $e->getMessage(),
            'hint'  => MagicLang::getMsg('hint_permissions'),
        ];
    }

    /**
     * Чем можно резать изображения.
     *
     * Утилиты внешние, и без них ресайз молча не работает, поэтому список видно
     * на главной админки — там же, где остальные проверки установки.
     */
    private function imageTools(): array
    {
        $tools = [];

        foreach (self::IMAGE_TOOLS as $name => $tool) {
            $lines = [];
            $code  = 1;

            // по коду возврата, а не по выводу: «vips: not found» приходит
            // текстом и выглядит как удачный ответ
            @exec($tool['version'] . ' 2>/dev/null', $lines, $code);

            $tools[] = [
                'name'    => $name,
                'ok'      => $code === 0,
                'version' => $code === 0 ? trim((string) ($lines[0] ?? '')) : '',
                'hint'    => $code === 0 ? '' : $tool['hint'],
            ];
        }

        foreach (self::PHP_EXTENSIONS as $extension => $hint) {
            $ok = extension_loaded($extension);

            $tools[] = [
                'name'    => 'php ' . $extension,
                'ok'      => $ok,
                'version' => $ok ? phpversion($extension) ?: 'ok' : '',
                'hint'    => $ok ? '' : $hint,
            ];
        }

        return $tools;
    }

    public function testWrite()
    {
        $testArray = MAGIC_FILE_ROLES;

        // try внутри цикла: каталоги друг от друга не зависят, и ошибка одного
        // не должна оставлять остальные без ответа
        foreach ($testArray as $index => $item) {
            $dir = $item['value'];
            $operation = '';

            try {
                // создание директории
                if (!File::isDirectory($dir)) {
                    $operation = "create directory $dir";
                    File::ensureDirectoryExists($dir, 0775, true);
                }

                $timestamp = time();
                $file = $dir . DIRECTORY_SEPARATOR . "{$timestamp}_testfile.txt";

                // запись
                $operation = "write to file $file";
                File::put($file, $timestamp);

                // удаление
                $operation = "delete file $file";
                File::delete($file);

                $testArray[$index]['result'] = 'ok';
            } catch (\Throwable $th) {
                $testArray[$index]['result'] = "$operation — " . $th->getMessage();
            }
        }

        return redirect()->back()->with('testWriteStatus', $testArray);
    }

    public function clearCache()
    {
        $clearCacheStatus = [
            'cache'  => Artisan::call('cache:clear'),
            'config' => Artisan::call('config:clear'),
            'route'  => Artisan::call('route:clear'),
            'view'   => Artisan::call('view:clear'),
            'event'  => Artisan::call('event:clear'),
        ];

        return redirect()->back()->with('clearCacheStatus', $clearCacheStatus);
    }

    public function artList()
    {
        $articles = Article::orderBy('name')
            ->orderBy('updated_at')
            ->paginate(self::ART_PER_PAGE);

        return view('magicAdmin::artList', compact('articles'));
    }

    public function adminList()
    {
        $users = MagicProUser::orderBy('email')->get();

        return view('magicAdmin::adminList', compact('users'));
    }
}
