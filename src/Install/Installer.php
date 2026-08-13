<?php

namespace MagicProSrc\Install;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use MagicProAdminControllers\API_ArticlesPostController;
use MagicProDatabaseModels\Article;
use MagicProDatabaseModels\MagicProUser;
use MagicProSrc\Scheduling\Heartbeat;

require_once __DIR__ . '/../../admin/controller/MagicProBuilder.php';

/**
 * Installation of MagicPro: runs on entering the admin panel.
 *
 * Two rules shape the class.
 *
 * The mark of a finished installation is written only after everything has
 * worked. Before that the whole thing repeats on every visit, so an install
 * interrupted in the middle — no tables yet, no write permissions — heals
 * itself on the next page load instead of staying broken forever.
 *
 * A check that fails stops the installation with an exception and leaves a
 * message saying what to run. Nothing here reports a problem without saying
 * what to do about it.
 *
 * Messages are collected in markdown and English only: they travel to the
 * screen and to the log at once, and the log is read by whoever is fixing the
 * server, not by the visitor.
 */
class Installer
{
    /** Log of the installation. Own file, no rotation: it is the history. */
    private const LOG = 'logs/install.log';

    /** The scheduler is considered alive while its mark is younger than this. */
    private const CRON_MAX_AGE = 120;

    /** Tools that cut images: how to ask for a version, how to install. */
    private const IMAGE_TOOLS = [
        'cwebp' => ['version' => 'cwebp -version', 'hint' => 'apt install webp'],
        'vips'  => ['version' => 'vips --version', 'hint' => 'apt install libvips-tools'],
    ];

    /** The same for php extensions. gd rotates photos by exif before cwebp. */
    private const PHP_EXTENSIONS = [
        'gd' => 'apt install php-gd && systemctl reload php-fpm',
    ];

    /** Messages for the screen, markdown. */
    private array $msgArr = [];

    /**
     * The whole installation. Returns ready html for the page.
     *
     * No lock around it: the cache store may itself live in a database that has
     * not been migrated yet, and then taking a lock would break the very page
     * that is supposed to explain the problem.
     */
    public function run(): string
    {
        if (! $this->installed()) {
            try {
                $this->msgArr[] = '## Start installation';
                $this->writeLog('-------- Start installation');

                $this->checkAdmin();
                $this->checkDirectories();
                $this->checkSymLink();
                $this->checkArticles();
                $this->regenerateArticles();

                File::put(MAGIC_INSTALL_FILE, 'install');

                $this->msgArr[] = 'Installation success.';
                $this->writeLog('installation success');
            } catch (\Throwable $e) {
                $this->writeLog('installation error aborted');

                return $this->html();
            }
        }

        try {
            $this->checkAssets();
        } catch (\Throwable $e) {
            $this->writeLog('assets error aborted');

            return $this->html();
        }

        $this->checkEnvironment();

        return $this->html();
    }

    /** Installation is done when the mark exists and says so. */
    private function installed(): bool
    {
        return is_file(MAGIC_INSTALL_FILE)
            && trim((string) file_get_contents(MAGIC_INSTALL_FILE)) === 'install';
    }

    /** Messages of this run as html. Nothing to say — empty string. */
    private function html(): string
    {
        if (! $this->msgArr) {
            return '';
        }

        return Str::markdown(implode("\n\n", $this->msgArr));
    }

    public function writeLog(string $msg): void
    {
        File::append(
            storage_path(self::LOG),
            date('Y-m-d H:i:s') . ' ' . $msg . PHP_EOL
        );
    }

    /** Message for the screen and the log at once, then stop. */
    private function fail(string $msg): void
    {
        $this->msgArr[] = $msg;
        $this->writeLog(strip_tags($msg));

        throw new \RuntimeException($msg);
    }

    // ==================================================================
    //                     checks that may stop us
    // ==================================================================

    public function testCreateWriteDirectory(string $absDir): void
    {
        if (! is_dir($absDir) && ! @mkdir($absDir, 0775, true) && ! is_dir($absDir)) {
            $this->fail("**Cannot create directory** `{$absDir}`\n\nRun: `sudo chown -R :www-data " . dirname($absDir) . '`');
        }

        // The directory may exist and still be closed for writing: created by
        // root, moved from another server. Only an actual write tells.
        $probe = $absDir . DIRECTORY_SEPARATOR . 'write_test.tmp';

        if (@file_put_contents($probe, 'test') === false) {
            $this->fail("**Cannot write to** `{$absDir}`\n\nRun: `sudo chown -R :www-data {$absDir}`");
        }

        @unlink($probe);

        $this->writeLog('directory ok: ' . $absDir);
    }

    public function testDirectory(string $absDir): void
    {
        if (! is_dir($absDir)) {
            $this->fail("**Directory not found** `{$absDir}`\n\nThe package is installed incompletely, run `composer update dixipro/magicpro`.");
        }

        $this->writeLog('directory exists: ' . $absDir);
    }

    public function checkAdmin(): void
    {
        if (! Schema::hasTable('magicPro_users')) {
            $this->fail("**No table of users.**\n\nRun: `php artisan migrate`");
        }

        if (! MagicProUser::query()->exists()) {
            $this->fail("**No admin to log in with.**\n\nRun: `php artisan magicpro:admin`");
        }

        $this->writeLog('admin ok');
    }

    public function checkDirectories(): void
    {
        $create = [
            MAGIC_DATA_DIR,
            MAGIC_VIEW_DIR,
            MAGIC_CONTROLLER_DIR,
            public_path(\MagicProSrc\Config\MagicGlobals::$INI['PUBLIC_UPLOAD_DIR']),
            VENDOR_PUBLIC,
            STATIC_HTML_CREATE_DIR,
        ];

        foreach ($create as $dir) {
            $this->testCreateWriteDirectory($dir);
        }

        // Comes with the package, we never create it: missing means the package
        // itself did not arrive whole.
        $this->testDirectory(VENDOR_FROM);
    }

    public function checkSymLink(): void
    {
        $link = public_path('storage');

        // A dead link answers true to is_link and gives no files: images stop
        // being served while everything looks fine.
        if (! is_dir($link) && ! (is_link($link) && is_dir(readlink($link)))) {
            $this->fail("**No link `public/storage`.**\n\nRun: `php artisan storage:link`");
        }

        $this->writeLog('storage link ok');
    }

    /**
     * Tables, root and a real article written end to end.
     *
     * The test article is the only way to learn that generation works: it goes
     * through the same path as any article of the site and leaves nothing
     * behind.
     */
    public function checkArticles(): void
    {
        if (! Schema::hasTable('articles')) {
            $this->fail("**No table of articles.**\n\nRun: `php artisan migrate`");
        }

        $this->createRoot();

        $this->testArticle();

        $this->writeLog('articles ok');
    }

    /**
     * Articles the site cannot live without: the root of the tree, the page the
     * router answers with when nothing is found, and the home page.
     *
     * They used to be inserted by the migration, which only ever runs once and
     * says nothing when its insert is skipped. Here they are checked on every
     * install: the root may well be in place while 404 is gone.
     */
    private function createRoot(): void
    {
        if (! Article::find(1)) {
            try {
                $root = new Article();
                $root->id          = 1;
                $root->name        = 'root';
                $root->title       = 'root';
                $root->parentId    = 0;
                $root->routeParams = $this->defaultRouteParams();
                $root->save();
            } catch (\Throwable $e) {
                $this->fail("**Cannot create the root article.**\n\n`" . $e->getMessage() . '`');
            }

            $this->writeLog('root created');
        }

        if (! Article::where('name', ART_NAME_404)->exists()) {
            try {
                $error = new Article();
                $error->name        = ART_NAME_404;
                $error->title       = ART_NAME_404;
                $error->parentId    = 1;
                $error->npp         = (int) DB::table('articles')->where('parentId', 1)->max('npp') + 1;
                $error->body        = '<p>Error 404</p>';
                $error->routeParams = $this->defaultRouteParams();
                $error->save();

                // У корня появился ребёнок, иначе дерево в админке его не раскроет.
                Article::where('id', 1)->update(['directory' => true]);
            } catch (\Throwable $e) {
                $this->fail('**Cannot create the ' . ART_NAME_404 . " article.**\n\n`" . $e->getMessage() . '`');
            }

            $this->writeLog(ART_NAME_404 . ' created');
        }

        if (! Article::where('name', 'index')->exists()) {
            try {
                $index = new Article();
                $index->name        = 'index';
                $index->title       = 'index';
                $index->parentId    = 1;
                $index->npp         = (int) DB::table('articles')->where('parentId', 1)->max('npp') + 1;
                $index->body        = '<p>Index page</p>';
                $index->isRoute     = true;
                $index->routeParams = ['utmParamsEnable' => true] + $this->defaultRouteParams();
                $index->save();

                Article::where('id', 1)->update(['directory' => true]);
            } catch (\Throwable $e) {
                $this->fail("**Cannot create the index article.**\n\n`" . $e->getMessage() . '`');
            }

            $this->writeLog('index created');
        }
    }

    /** Полный набор ключей маршрута, всё выключено. */
    private function defaultRouteParams(): array
    {
        return [
            'useController'   => false,
            'adminOnly'       => false,
            'utmParamsEnable' => false,
            'getEnable'       => false,
            'postEnable'      => false,
            'bindKeys'        => false,
            'keysArr'         => [],
        ];
    }

    /** Creates an article, checks both generated files, removes everything. */
    private function testArticle(): void
    {
        $name = 'install_' . date('Ymd_His');
        $article = null;

        try {
            $article = new Article();
            $article->name        = $name;
            $article->title       = $name;
            $article->parentId    = 1;
            $article->npp         = (int) DB::table('articles')->where('parentId', 1)->max('npp') + 1;
            $article->body        = '<p>installation test</p>';
            $article->routeParams = ['useController' => true];
            $article->save();

            \MagicProAdminControllers\createMpro($article->toArray());

            $view       = MAGIC_VIEW_DIR . '/' . $name . '.blade.php';
            $controller = MAGIC_CONTROLLER_DIR . '/' . $name . '.php';

            if (! is_file($view) || ! is_file($controller)) {
                $this->fail("**Article files are not generated.**\n\nExpected `{$view}` and `{$controller}`.");
            }

            \MagicProAdminControllers\deleteMpro($article->toArray());

            if (is_file($view) || is_file($controller)) {
                $this->fail("**Article files are not removed.**\n\nCheck the rights on `" . MAGIC_DATA_DIR . '`.');
            }
        } catch (\Throwable $e) {
            // Whatever went wrong, the article must not stay in the tree.
            $this->cleanTestArticle($article);

            throw $e;
        }

        $this->cleanTestArticle($article);
    }

    private function cleanTestArticle(?Article $article): void
    {
        if (! $article || ! $article->exists) {
            return;
        }

        try {
            \MagicProAdminControllers\deleteMpro($article->toArray());
            $article->delete();
        } catch (\Throwable $e) {
            $this->writeLog('test article cleanup failed: ' . $e->getMessage());
        }
    }

    private function regenerateArticles(): void
    {
        $res = API_ArticlesPostController::run(['command' => 'regenerateAll']);

        if (! $res['status']) {
            $this->fail("**Articles are not generated.**\n\n`" . $res['errorMsg'] . '`');
        }

        $this->writeLog('articles regenerated: ' . count($res['data']));
    }

    /**
     * Assets of the admin panel in public.
     *
     * Version is the only thing to go by: the copied files themselves say
     * nothing about which release they came from.
     */
    public function checkAssets(): void
    {
        $versionFile = VENDOR_PUBLIC . '/version.txt';
        $installed   = is_file($versionFile) ? trim((string) file_get_contents($versionFile)) : null;

        if ($installed === MAGIC_VERSION) {
            return;
        }

        try {
            File::copyDirectory(VENDOR_FROM, VENDOR_PUBLIC);
            File::put($versionFile, MAGIC_VERSION);
        } catch (\Throwable $e) {
            $this->fail("**Cannot copy the assets** to `" . VENDOR_PUBLIC . "`\n\n`" . $e->getMessage() . '`');
        }

        $this->writeLog('assets copied: ' . MAGIC_VERSION);
    }

    // ==================================================================
    //                     tells, never stops
    // ==================================================================

    /**
     * Environment: what we can neither create nor repair.
     *
     * Missing cwebp is not a reason to stop the page, so nothing here throws —
     * every line is a message with the command that installs what is missing.
     */
    public function checkEnvironment(): void
    {
        foreach (self::IMAGE_TOOLS as $name => $tool) {
            $lines = [];
            $code  = 1;

            // by the exit code, not by the output: "vips: not found" arrives as
            // text and reads like a successful answer
            @exec($tool['version'] . ' 2>/dev/null', $lines, $code);

            if ($code !== 0) {
                $this->msgArr[] = "**{$name} not found.** Images are not resized.\n\nRun: `{$tool['hint']}`";
                $this->writeLog($name . ' not found');
            }
        }

        foreach (self::PHP_EXTENSIONS as $extension => $hint) {
            if (! extension_loaded($extension)) {
                $this->msgArr[] = "**php {$extension} is missing.**\n\nRun: `{$hint}`";
                $this->writeLog('php ' . $extension . ' is missing');
            }
        }

        $this->checkCron();
    }

    /**
     * The scheduler lives outside and reports to nobody, so it is judged by the
     * mark Heartbeat leaves every minute.
     */
    private function checkCron(): void
    {
        $file = storage_path(Heartbeat::FILE);

        if (is_file($file) && (time() - (int) filemtime($file)) <= self::CRON_MAX_AGE) {
            return;
        }

        $note = is_file($file)
            ? 'Cron is not working. Last mark: ' . date('Y-m-d H:i:s', (int) filemtime($file)) . '.'
            : 'Cron is not configured, the scheduler has never run.';

        // The same command as in Readme, with base_path() instead of $(pwd) and
        // the full path to php: cron has almost no PATH of its own.
        $command = '(sudo crontab -u www-data -l 2>/dev/null; echo "* * * * * cd ' . base_path()
            . ' && /usr/bin/php artisan schedule:run >> /dev/null 2>&1") | sort -u | sudo crontab -u www-data -';

        $this->msgArr[] = "**{$note}** Scheduled mail is not sent.\n\nRun: `{$command}`";
        $this->writeLog($note);
    }
}
