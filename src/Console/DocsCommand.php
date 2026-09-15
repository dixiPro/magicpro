<?php

namespace MagicProSrc\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use MagicProSrc\Docs\PhpDoc;

/**
 * Builds the documentation pages that are written in the source.
 *
 * The doc of a helper lives in the phpdoc above it: a page assembled from the
 * source cannot go stale, while a markdown file describing the same methods
 * parts with the code on the first rename. But an agent working over МСП has
 * neither the source nor a shell — it reads `docs/`. So the page is assembled
 * here and put there as an ordinary file: visible in the repository, found by
 * `list-docs` without a special case, readable by eye.
 *
 * The price of a file is that it lags behind the source until the command is
 * run again. Hence the header written into it, and hence this command belongs
 * to the release: helpers change about once a week, the page is rebuilt with
 * them.
 */
class DocsCommand extends Command
{
    protected $signature = 'magicpro:docs';

    protected $description = 'Builds the documentation pages written in phpdoc';

    /**
     * What is built and where it goes.
     *
     * One line per page: the class the text lives in, the file it becomes and
     * the heading of that file. The language is the one of the path.
     */
    private const PAGES = [
        [
            'class' => \MproHelper::class,
            'file'  => 'ru/helpers/use.md',
            'title' => 'Хелперы: как пользоваться',
        ],
    ];

    /** Said in the file itself, to whoever opens it to edit. */
    private const HEADER = '<!-- Собрано командой `php artisan magicpro:docs` из phpdoc класса %s.'
        . "\n     Руками не править: правка потеряется при следующей сборке. -->";

    public function handle(): int
    {
        foreach (self::PAGES as $page) {
            $path = __DIR__ . '/../../docs/' . $page['file'];
            $lang = strtok($page['file'], '/');

            $text = $this->page($page['class'], (string) $lang, $page['title']);

            File::ensureDirectoryExists(dirname($path));
            File::put($path, $text);

            $this->info($page['file'] . ' — ' . mb_strlen($text) . ' characters');
        }

        return self::SUCCESS;
    }

    /** The whole page: header, title, the block of the class, the methods. */
    private function page(string $class, string $lang, string $title): string
    {
        $short   = (new \ReflectionClass($class))->getShortName();
        $methods = PhpDoc::methods($class, $lang, false);

        $out = [
            sprintf(self::HEADER, $short),
            '',
            '# ' . $title,
        ];

        $about = PhpDoc::about($class, $lang, false);

        if ($about !== '') {
            $out[] = '';
            $out[] = $about;
        }

        $out[] = '';
        $out[] = '## Методы';
        $out[] = '';
        $out[] = $this->contents($methods);

        foreach ($methods as $method) {
            $out[] = '';
            $out[] = '### ' . $method['name'];
            $out[] = '';
            $out[] = '```php';
            $out[] = $short . '::' . $method['signature'];
            $out[] = '```';
            $out[] = '';
            $out[] = $method['documented'] ? $method['doc'] : '_Описания нет._';
        }

        return implode("\n", $out) . "\n";
    }

    /**
     * The list of names at the top.
     *
     * The heading of a method is its name alone, so the anchor is the name in
     * lower case — nothing has to be invented for the links, and a signature in
     * a heading would have made them unreadable.
     */
    private function contents(array $methods): string
    {
        $links = [];

        foreach ($methods as $method) {
            $links[] = '[' . $method['name'] . '](#' . mb_strtolower($method['name']) . ')';
        }

        return implode(' · ', $links);
    }
}
