<?php

namespace MagicProSrc\Scheduling;

use Illuminate\Http\Request;
use MagicProDatabaseModels\Article;
use MagicProDatabaseModels\MagicProCronTask;
use MagicProSrc\Config\MagicGlobals;

/**
 * Calls the controller of a cron task.
 *
 * Does what DynamicRouteHandler does right before it hands control over — the
 * same Env in the request attributes, the same \MagicProControllers\{name}
 * class, the same handle() — only without parsing a URL and without the route
 * checks: the task was created by an admin in the admin panel, and that is the
 * authorisation. The view is rendered because handle() renders it; the
 * response is thrown away.
 *
 * The try/catch here is not about judging the result. It protects the
 * neighbours: one broken task must not take down the whole scheduler pass.
 * Only troubles of cron itself land in it — no article, no class, database
 * gone. An error inside process() is caught by MagicController::handle()
 * itself and turned into a 500 page, which we drop.
 */
class CronTaskRunner
{
    /** Log file name, storage/logs/cron.log. */
    public const LOG = 'cron';

    /** Returns how long the call took, in milliseconds. */
    public static function run(MagicProCronTask $task): int
    {
        $start  = microtime(true);
        $params = $task->params ?? [];

        try {
            $article = Article::where('name', $task->controller)->first();

            if (! $article) {
                throw new \Exception(CronTaskChecker::ERRORS['article_missing']);
            }

            $env = [
                'name'     => $article->name,
                'title'    => $article->title,
                'artId'    => $article->id,
                'parentId' => $article->parentId,
                'view'     => 'magic::' . $article->name,
            ];

            $request = Request::create('/' . $article->name, 'POST', $params);
            $request->attributes->add($env);

            // Stamped before the call: the mark says the scheduler reached this
            // task, not that the controller did its job.
            $task->forceFill(['last_run_at' => now()])->save();

            $class      = '\\MagicProControllers\\' . $article->name;
            $controller = new $class();

            $controller->handle([
                'request'    => $request,
                'getParams'  => [],
                'postParams' => $params,
            ]);

            $ms = self::ms($start);

            if (MagicGlobals::$INI['CRON_LOG_SUCCESS'] ?? true) {
                \MproHelper::addLog(self::LOG, [
                    'id'         => $task->id,
                    'name'       => $task->name,
                    'controller' => $task->controller,
                    'params'     => $params,
                    'ms'         => $ms,
                ]);
            }

            return $ms;
        } catch (\Throwable $e) {
            $ms = self::ms($start);

            // Troubles of cron are written whatever the setting says.
            \MproHelper::addLog(self::LOG, [
                'id'         => $task->id,
                'name'       => $task->name,
                'controller' => $task->controller,
                'error'      => $e->getMessage(),
                'ms'         => $ms,
            ]);

            return $ms;
        }
    }

    private static function ms(float $start): int
    {
        return (int) round((microtime(true) - $start) * 1000);
    }
}
