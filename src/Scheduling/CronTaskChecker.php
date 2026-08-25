<?php

namespace MagicProSrc\Scheduling;

use MagicProDatabaseModels\Article;

/**
 * Is this task callable at all?
 *
 * A task points at one public method of one controller: `article|method`. The
 * check is deliberately short — the article exists and its controller class
 * exists. Whether the method is there, whether it takes what the task sends,
 * whether it does the right thing: that is what the "run now" button is for,
 * and its error goes straight to the screen.
 *
 * Three callers need the same answer: `save` refuses to store a task that
 * points at nothing, `list` marks the broken ones (an article can be renamed
 * or deleted long after the task was created) and `runNow` refuses to fire.
 *
 * CronTaskRunner does not call it: it is wrapped in try/catch anyway, and
 * asking the database once a minute for something the admin panel already
 * shows is a waste.
 */
class CronTaskChecker
{
    /** What separates the article from the method in the `controller` field. */
    public const SEPARATOR = '|';

    /** The first check that fails is the answer, in words a human can read. */
    public const ERRORS = [
        'article_required' => 'article required, write article' . self::SEPARATOR . 'method',
        'method_required'  => 'method required, write article' . self::SEPARATOR . 'method',
        'method_invalid'   => 'method name must start with a letter and hold letters, digits and underscore only',
        'article_missing'  => 'article not found',
        'class_missing'    => 'controller class not found',
    ];

    /**
     * Splits the `controller` field of a task.
     *
     * Never throws: an unusable string comes back with empty parts, and it is
     * check() that turns that into words.
     *
     * @return array{article: string, method: string}
     */
    public static function parse(string $controller): array
    {
        $parts = explode(self::SEPARATOR, trim($controller), 2);

        return [
            'article' => trim($parts[0] ?? ''),
            'method'  => trim($parts[1] ?? ''),
        ];
    }

    /** The class the article generates, whether or not it exists yet. */
    public static function className(string $article): string
    {
        return '\\MagicProControllers\\' . $article;
    }

    /**
     * @return array{ok: bool, error: string}
     */
    public static function check(string $controller): array
    {
        ['article' => $article, 'method' => $method] = self::parse($controller);

        if ($article === '') {
            return self::fail('article_required');
        }

        if ($method === '') {
            return self::fail('method_required');
        }

        // A name that starts with a letter can never be __construct or any
        // other magic method: one rule instead of a list of bans.
        if (! preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $method)) {
            return self::fail('method_invalid');
        }

        if (! Article::where('name', $article)->exists()) {
            return self::fail('article_missing');
        }

        if (! class_exists(self::className($article))) {
            return self::fail('class_missing');
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
