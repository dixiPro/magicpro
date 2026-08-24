<?php

namespace MagicProSrc\Scheduling;

use MagicProDatabaseModels\Article;
use MagicProSrc\MagicController;

/**
 * Is this article usable as a cron task?
 *
 * One place for the whole list, because three callers need the same answer:
 * `save` refuses to store a task that would never run, `list` marks the broken
 * ones (an article can be renamed or deleted long after the task was created)
 * and `runNow` refuses to fire.
 *
 * CronTaskRunner does not call it: it is wrapped in try/catch anyway, and
 * asking the database once a minute for something the admin panel already
 * shows is a waste.
 */
class CronTaskChecker
{
    /** The first check that fails is the answer, in words a human can read. */
    public const ERRORS = [
        'article_missing'    => 'article not found',
        'not_route'          => 'article is not a route',
        'no_controller'      => 'article is rendered without a controller (useController is off)',
        'class_missing'      => 'controller class not found',
        'not_magic'          => 'controller does not extend MagicController',
        'not_admin_only'     => 'article must be adminOnly',
        'post_not_enabled'   => 'article must allow post (postEnable)',
    ];

    /**
     * @return array{ok: bool, error: string}
     */
    public static function check(string $name): array
    {
        $article = Article::where('name', $name)->first();

        if (! $article) {
            return self::fail('article_missing');
        }

        if (! $article->isRoute) {
            return self::fail('not_route');
        }

        $routeParams = $article->routeParams ?? [];

        // The key is present and false — the article renders straight from the
        // blade and no controller class exists. Same reading as in
        // DynamicRouteHandler.
        if (array_key_exists('useController', $routeParams) && ! $routeParams['useController']) {
            return self::fail('no_controller');
        }

        $class = '\\MagicProControllers\\' . $article->name;

        if (! class_exists($class)) {
            return self::fail('class_missing');
        }

        // Components and livewire classes share that namespace but have no
        // handle(): cron would die with a fatal on such a class.
        if (! is_subclass_of($class, MagicController::class)) {
            return self::fail('not_magic');
        }

        if (empty($routeParams['adminOnly'])) {
            return self::fail('not_admin_only');
        }

        if (empty($routeParams['postEnable'])) {
            return self::fail('post_not_enabled');
        }

        return [
            'ok'    => true,
            'error' => '',
        ];
    }

    private static function fail(string $key): array
    {
        return [
            'ok'    => false,
            'error' => self::ERRORS[$key],
        ];
    }
}
