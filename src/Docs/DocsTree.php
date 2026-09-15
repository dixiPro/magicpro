<?php

namespace MagicProSrc\Docs;

use MagicProSrc\Config\MagicGlobals;

/**
 * The documentation as the admin panel shows it: a tree and a page.
 *
 * The order and the names of the sections live in `docs/<lang>/index.json` and
 * nowhere else. A walk of the folder cannot give them: it sees files, not what
 * to read first and what a file is called in human words.
 *
 * The language is taken as it is, without falling back to another one. No
 * `index.json` for a language means there is no documentation in it, and that
 * is what the section says — better than a page of Russian shown to somebody
 * who asked for English.
 */
class DocsTree
{
    private const DIR = __DIR__ . '/../../docs';

    private const INDEX = 'index.json';

    /** A link of the index: a path inside the language, and nothing else. */
    private const LINK = '#^[A-Za-z0-9_-]+(/[A-Za-z0-9_-]+)*\.md$#';

    public static function lang(): string
    {
        return (string) (MagicGlobals::$INI['LANGUAGE'] ?? 'ru');
    }

    /**
     * The tree as the index describes it, with a flag on every entry saying
     * whether the file is really there.
     *
     * The flag is what the missing translation looks like on the screen: the
     * line stays, so the reader sees the section exists, and says that there is
     * no documentation for it yet.
     */
    public static function tree(string $lang): array
    {
        $index = self::index($lang);

        return $index === [] ? [] : self::mark($index['group'] ?? [], $lang);
    }

    /** The name of the whole set, from the index. */
    public static function title(string $lang): string
    {
        return (string) (self::index($lang)['name'] ?? '');
    }

    /** One page as html, or an empty string when there is no such file. */
    public static function page(string $lang, string $link): string
    {
        $path = self::path($lang, $link);

        if ($path === '') {
            return '';
        }

        return \MproHelper::mdToHtml((string) file_get_contents($path));
    }

    /** The first page that exists: what opens when nothing is chosen. */
    public static function first(string $lang): string
    {
        foreach (self::flat(self::tree($lang)) as $row) {
            if ($row['exists']) {
                return $row['link'];
            }
        }

        return '';
    }

    /**
     * The tree in one line: group, name, link, and whether the file is there.
     *
     * What МСП hands to an agent: the same order and the same names a person
     * sees in the admin panel, instead of a walk of the folder guessing a
     * description from the first line of a file.
     *
     * @return array<int, array{group: string, name: string, link: string, about: string, exists: bool, agent: bool}>
     */
    public static function pages(string $lang): array
    {
        $pages = [];

        foreach (self::tree($lang) as $group) {
            $name = (string) ($group['name'] ?? '');

            foreach (self::flat([$group]) as $row) {
                $pages[] = ['group' => $name] + $row;
            }
        }

        return $pages;
    }

    /** The name of a page in the index, to show above it. */
    public static function name(string $lang, string $link): string
    {
        foreach (self::flat(self::tree($lang)) as $row) {
            if ($row['link'] === $link) {
                return $row['name'];
            }
        }

        return '';
    }

    /**
     * A language that really has an index.
     *
     * The admin panel is strict on purpose: asked for English, it says there is
     * no English documentation rather than showing Russian. For an agent that
     * would mean no documentation at all — the default `LANGUAGE` is `en` and
     * the package carries `ru` — so МСП takes what exists.
     */
    public static function langWithDocs(): string
    {
        $lang = self::lang();

        if (self::index($lang) !== []) {
            return $lang;
        }

        foreach ((array) glob(self::DIR . '/*/' . self::INDEX) as $file) {
            $found = basename(dirname((string) $file));

            if (self::index($found) !== []) {
                return $found;
            }
        }

        return $lang;
    }

    /**
     * Files of the language that no line of the index mentions.
     *
     * The index gives order and names, but it is written by hand and parts with
     * the folder silently: a new page simply never appears on the screen, and
     * nobody notices a line that is not there. So the folder is walked once and
     * compared with the index.
     *
     * @return array<int, array{link: string, readable: bool}>
     */
    public static function orphans(string $lang): array
    {
        if (! preg_match('/^[A-Za-z-]{2,10}$/', $lang)) {
            return [];
        }

        $root = realpath(self::DIR . '/' . $lang);

        if ($root === false) {
            return [];
        }

        $known = array_column(self::pages($lang), 'link');
        $found = [];

        $walk = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($walk as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'md') {
                continue;
            }

            $link = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));

            if (in_array($link, $known, true)) {
                continue;
            }

            // a name the reader does not accept cannot be opened by a link:
            // such a file is shown by name alone
            $found[] = ['link' => $link, 'readable' => (bool) preg_match(self::LINK, $link)];
        }

        usort($found, fn($a, $b) => strcmp($a['link'], $b['link']));

        return $found;
    }

    private static function index(string $lang): array
    {
        if (! preg_match('/^[A-Za-z-]{2,10}$/', $lang)) {
            return [];
        }

        $file = self::DIR . '/' . $lang . '/' . self::INDEX;

        if (! is_file($file)) {
            return [];
        }

        $index = json_decode((string) file_get_contents($file), true);

        return is_array($index) ? $index : [];
    }

    /** The file of a page on disk, for downloading it as it is written. */
    public static function file(string $lang, string $link): string
    {
        return self::path($lang, $link);
    }

    /** @return string full path of the file, or an empty string */
    private static function path(string $lang, string $link): string
    {
        if (! preg_match('/^[A-Za-z-]{2,10}$/', $lang) || ! preg_match(self::LINK, $link)) {
            return '';
        }

        $file = self::DIR . '/' . $lang . '/' . $link;

        return is_file($file) ? $file : '';
    }

    /**
     * Walks the groups and adds two things to every entry with a link: whether
     * the file is there, and whether МСП is allowed to offer it.
     *
     * `agent` is off unless the index says otherwise, and a group can say it
     * for all of its pages at once. The default is deliberate: an agent reads
     * what it was given, so what it does not need it should not see. A page
     * kept from it is still on the screen of the admin panel.
     */
    private static function mark(array $rows, string $lang, bool $agent = false): array
    {
        foreach ($rows as $i => $row) {
            $own = (bool) ($row['agent'] ?? $agent);

            if (isset($row['group'])) {
                $rows[$i]['group'] = self::mark((array) $row['group'], $lang, $own);

                continue;
            }

            $rows[$i]['exists'] = isset($row['link']) && self::path($lang, (string) $row['link']) !== '';
            $rows[$i]['agent']  = $own;
        }

        return $rows;
    }

    /** The tree in one line, for looking things up. */
    private static function flat(array $rows): array
    {
        $found = [];

        foreach ($rows as $row) {
            if (isset($row['group'])) {
                $found = array_merge($found, self::flat((array) $row['group']));

                continue;
            }

            if (! empty($row['link'])) {
                $found[] = [
                    'name'   => (string) ($row['name'] ?? ''),
                    'link'   => (string) $row['link'],
                    // одна строка «о чём страница»: агенту — чтобы знать, что
                    // открывать, не читая всё подряд; человеку — подпись
                    'about'  => (string) ($row['about'] ?? ''),
                    'exists' => (bool) ($row['exists'] ?? false),
                    'agent'  => (bool) ($row['agent'] ?? false),
                ];
            }
        }

        return $found;
    }
}
