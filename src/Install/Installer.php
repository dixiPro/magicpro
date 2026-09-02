<?php

namespace MagicProSrc\Install;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use MagicProAdminControllers\API_ArticlesPostController;
use MagicProDatabaseModels\Article;
use MagicProDatabaseModels\MagicProUser;
use MagicProSrc\Ai\AiConfig;
use MagicProSrc\Mail\AwsHookHandler;
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

    /**
     * How long we wait for the hook to answer its own site.
     *
     * Short on purpose: the request goes out and comes back into the same
     * server, and a page of the admin panel waits for it. Better a wrong «does
     * not answer» on a loaded server than a start page hanging for a minute.
     */
    private const HOOK_TIMEOUT = 5;

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
     * Checks that passed: ['key' => a key of the lang file, 'note' => detail].
     *
     * Troubles are written in English, they go to the log as well and are read
     * by whoever fixes the server. This list is read by the visitor of the
     * admin panel instead, so it is translated — the key is resolved in the
     * blade, the note is a number, a path or a time and needs no translation.
     */
    private array $okArr = [];

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

    /**
     * Checks that passed, for the page.
     *
     * Silence used to be the only sign that everything is in place, and
     * silence is not a report: this list says what exactly was looked at.
     *
     * @return array<int, array{key: string, note: string}>
     */
    public function okList(): array
    {
        return $this->okArr;
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

    /**
     * A check that passed.
     *
     * Nothing is written to the log here: the log keeps the history of an
     * installation, and a green line every time somebody opens the admin panel
     * would bury it.
     */
    private function ok(string $key, string $note = ''): void
    {
        $this->okArr[] = [
            'key'  => $key,
            'note' => $note,
        ];
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

        $admins = MagicProUser::query()->count();

        if (! $admins) {
            $this->fail("**No admin to log in with.**\n\nRun: `php artisan magicpro:admin`");
        }

        $this->ok('install_ok_admin', (string) $admins);
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

        $this->ok('install_ok_directories', (string) (count($create) + 1));
    }

    public function checkSymLink(): void
    {
        $link = public_path('storage');

        // A dead link answers true to is_link and gives no files: images stop
        // being served while everything looks fine.
        if (! is_dir($link) && ! (is_link($link) && is_dir(readlink($link)))) {
            $this->fail("**No link `public/storage`.**\n\nRun: `php artisan storage:link`");
        }

        $this->ok('install_ok_symlink', is_link($link) ? (string) readlink($link) : $link);
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

        $this->ok('install_ok_articles', (string) Article::query()->count());
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

        $this->ok('install_ok_regenerate', (string) count($res['data']));
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
            $this->ok('install_ok_assets', MAGIC_VERSION);

            return;
        }

        try {
            File::copyDirectory(VENDOR_FROM, VENDOR_PUBLIC);
            File::put($versionFile, MAGIC_VERSION);
        } catch (\Throwable $e) {
            $this->fail("**Cannot copy the assets** to `" . VENDOR_PUBLIC . "`\n\n`" . $e->getMessage() . '`');
        }

        $this->ok('install_ok_assets', MAGIC_VERSION);
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

                continue;
            }

            $this->ok('install_ok_image_tool', trim($name . ' ' . ($lines[0] ?? '')));
        }

        foreach (self::PHP_EXTENSIONS as $extension => $hint) {
            if (! extension_loaded($extension)) {
                $this->msgArr[] = "**php {$extension} is missing.**\n\nRun: `{$hint}`";
                $this->writeLog('php ' . $extension . ' is missing');

                continue;
            }

            $this->ok('install_ok_php_extension', $extension);
        }

        $this->checkCron();
        $this->checkAwsHook();
        $this->checkAiConfig();
        $this->checkTmux();
    }

    /**
     * Settings of the AI agent: the site must have its own copy.
     *
     * Checked on every visit, not only at the first installation: the file
     * arrives with a new version of the package, and the site that was
     * installed before it has none. Copying is the whole check — there is
     * nothing to keep in sync afterwards, the site edits its copy and the
     * package never touches it again.
     *
     * The password in the copy that comes with the package is empty, so the
     * section arrives switched off.
     */
    private function checkAiConfig(): void
    {
        if (AiConfig::ready()) {
            return;
        }

        try {
            File::ensureDirectoryExists(dirname(AiConfig::path()));
            File::copy(AiConfig::template(), AiConfig::path());

            // the file is about to hold a password: nobody outside the site
            @chmod(AiConfig::path(), 0640);
        } catch (\Throwable $e) {
            $this->msgArr[] = '**Cannot put the settings of the AI agent** into `'
                . AiConfig::path() . "`\n\n`" . $e->getMessage() . '`';

            $this->writeLog('ai config copy failed: ' . $e->getMessage());

            return;
        }

        $this->ok('install_ok_ai_config', AiConfig::path());
        $this->writeLog('ai config copied to ' . AiConfig::path());
    }

    /**
     * tmux, the session of the AI agent lives in it.
     *
     * Asked about only where the section is switched on, that is where the
     * settings hold a password: a site that never starts an agent has no use
     * for tmux — a red line about it would be a lie.
     *
     * By the exit code, like the rest of the tools: "tmux: not found" arrives
     * as text and reads like a successful answer.
     */
    private function checkTmux(): void
    {
        if (! AiConfig::on()) {
            return;
        }

        $lines = [];
        $code  = 1;

        @exec('tmux -V 2>/dev/null', $lines, $code);

        if ($code === 0) {
            $this->ok('install_ok_tmux', trim($lines[0] ?? ''));

            return;
        }

        $this->msgArr[] = "**tmux not found.** The AI agent of the `MCP` section will not start."
            . "\n\nRun: `sudo apt install tmux`";

        $this->writeLog('tmux not found');
    }

    /**
     * The scheduler lives outside and reports to nobody, so it is judged by the
     * mark Heartbeat leaves every minute.
     */
    private function checkCron(): void
    {
        $file = storage_path(Heartbeat::FILE);

        if (is_file($file) && (time() - (int) filemtime($file)) <= self::CRON_MAX_AGE) {
            $this->ok('install_ok_cron', date('Y-m-d H:i:s', (int) filemtime($file)));

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

    /**
     * The address AWS knocks at with the fate of every letter, and whether it
     * still answers.
     *
     * The address is not taken from a setting: the site has one hook and it is
     * its own url. It is printed because it is what goes into `aws-setup.ini`
     * of the site — typed from memory, it ends up subscribed as something the
     * topic will call for three days without an answer.
     *
     * The knock repeats what `magicpro:aws-setup` does before it subscribes
     * the address in SNS: a POST with a Type of our own. The handler answers an unknown Type with
     * `{"status": true}`, while the dynamic router of the site — the usual
     * reason the address is silent — answers with a page. So the answer tells
     * not only that something is alive, but that the hook is.
     */
    private function checkAwsHook(): void
    {
        if (! Route::has('magic.awsHook')) {
            return;
        }

        $url = route('magic.awsHook');

        try {
            $answer = Http::timeout(self::HOOK_TIMEOUT)
                ->withoutRedirecting()
                ->post($url, ['Type' => AwsHookHandler::PING]);
        } catch (\Throwable $e) {
            $this->hookFail($url, 'does not answer: ' . $e->getMessage());

            return;
        }

        if ($answer->redirect()) {
            $this->hookFail($url, 'redirects to ' . $answer->header('Location'));

            return;
        }

        if (! $answer->successful()) {
            $this->hookFail($url, 'answers ' . $answer->status());

            return;
        }

        if ($answer->json('status') !== true) {
            $this->hookFail($url, 'answers, but not with the hook: the dynamic router took the address');

            return;
        }

        $this->ok('install_ok_aws_hook', $url);
    }

    /** One shape for every trouble of the hook: the address, then what happened. */
    private function hookFail(string $url, string $note): void
    {
        $this->msgArr[] = "**The AWS hook {$note}.** `{$url}`"
            . "\n\nEvents of sent mail are not recorded. `POST /awsHook` has to reach `AwsHookHandler`.";

        $this->writeLog('aws hook: ' . $note . ' ' . $url);
    }
}
