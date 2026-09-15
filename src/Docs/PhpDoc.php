<?php

namespace MagicProSrc\Docs;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Documentation of a class taken from its own source.
 *
 * The doc of a helper lives in the phpdoc above it and nowhere else: a page
 * that reads it cannot go stale, while a markdown file describing the same
 * methods parts with the code on the first rename.
 *
 * Two languages in one block, `@ru` and `@en`. Text before any of them, and a
 * block with neither, counts as `@en` — the rule is written down once here so
 * that a method nobody translated still shows something.
 *
 * A method without a phpdoc is not skipped: its signature goes on the page as
 * it is. The page is also the list of what still has no doc.
 */
class PhpDoc
{
    /**
     * Parsed classes, one json per class.
     *
     * The number in the path is the version of what lies inside. It changed
     * when the cache started keeping the markdown as it is written instead of
     * ready html: an old file has the wrong shape, and a folder of its own is
     * cheaper than a check on every read.
     */
    private const CACHE_DIR = 'app/private/magic/phpdoc/2';

    /** The language of the tag everything falls back to. */
    private const FALLBACK = 'en';

    /**
     * Public methods of the class with their doc, in the order they are written.
     *
     * `$renderHtml` false gives the markdown as it is written in the source:
     * that is what the console command needs, and the page needs html.
     *
     * @return array<int, array{name: string, signature: string, doc: string, documented: bool}>
     */
    public static function methods(string $class, string $lang = '', bool $renderHtml = true): array
    {
        $lang = $lang !== '' ? $lang : self::FALLBACK;

        $rows = [];

        foreach (self::parsed($class)['methods'] as $row) {
            $doc = $row['doc'][$lang] ?? $row['doc'][self::FALLBACK] ?? reset($row['doc']) ?: '';

            $rows[] = [
                'name'       => $row['name'],
                'signature'  => $row['signature'],
                'doc'        => $renderHtml ? Str::markdown($doc) : $doc,
                'documented' => $row['doc'] !== [],
            ];
        }

        return $rows;
    }

    /**
     * The block written above the class itself.
     *
     * The introduction of a page lives there: what the whole set is for and how
     * it is called. It belongs to the set and not to any single method, and the
     * only place where it stays next to the code is the class comment.
     */
    public static function about(string $class, string $lang = '', bool $renderHtml = true): string
    {
        $lang = $lang !== '' ? $lang : self::FALLBACK;

        $blocks = self::parsed($class)['about'];

        $text = $blocks[$lang] ?? $blocks[self::FALLBACK] ?? (reset($blocks) ?: '');

        return $renderHtml ? Str::markdown($text) : $text;
    }

    /**
     * The class as it was parsed last time, or parsed now.
     *
     * The cache is one file per class, and the only question asked of it is
     * whether it is younger than the source. No key, no ttl: the source is the
     * whole truth, and a file that is older than it is wrong by definition.
     */
    private static function parsed(string $class): array
    {
        $reflection = new ReflectionClass($class);
        $source     = (string) $reflection->getFileName();
        $cache      = storage_path(self::CACHE_DIR . '/' . $reflection->getShortName() . '.json');

        if ($source !== '' && is_file($cache) && filemtime($cache) >= filemtime($source)) {
            $rows = json_decode((string) file_get_contents($cache), true);

            if (is_array($rows)) {
                return $rows;
            }
        }

        $rows = self::read($reflection);

        // the cache is a convenience and not a condition: the page is served by
        // the web user and the console command by a person, and whichever of
        // them cannot write here has still parsed the class and has the answer
        try {
            File::ensureDirectoryExists(dirname($cache));
            File::put($cache, json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable) {
            //
        }

        return $rows;
    }

    /** Reads the class: its own block, own public methods, their blocks. */
    private static function read(ReflectionClass $reflection): array
    {
        $rows = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // inherited methods belong to the page of their own class
            if ($method->getDeclaringClass()->getName() !== $reflection->getName()) {
                continue;
            }

            $rows[] = [
                'name'      => $method->getName(),
                'signature' => self::signature($method),
                'doc'       => self::blocks((string) $method->getDocComment()),
            ];
        }

        return [
            'about'   => self::blocks((string) $reflection->getDocComment()),
            'methods' => $rows,
        ];
    }

    /**
     * The blocks of one comment as ready html, by language.
     *
     * Markdown, the same as the rest of the documentation: backticks and an
     * indented example are the whole markup a short doc needs, and both are
     * already in the source.
     *
     * @return array<string, string>
     */
    private static function blocks(string $comment): array
    {
        if (trim($comment) === '') {
            return [];
        }

        $text = [];
        $lang = self::FALLBACK;

        // the frame of the comment goes off, the indentation inside stays: an
        // example is written with it, and markdown makes it a code block
        foreach (explode("\n", $comment) as $line) {
            $line = (string) preg_replace('#^\s*/?\*+/?#', '', $line);
            $line = (string) preg_replace('#\s*\*/\s*$#', '', $line);

            if (str_starts_with($line, ' ')) {
                $line = substr($line, 1);
            }

            $tag = trim($line);

            if (preg_match('/^@([a-z]{2})$/', $tag, $m)) {
                $lang = $m[1];

                continue;
            }

            $text[$lang][] = $line;
        }

        $blocks = [];

        foreach ($text as $code => $lines) {
            $body = trim(implode("\n", $lines));

            if ($body !== '') {
                $blocks[$code] = $body;
            }
        }

        return $blocks;
    }

    /** The call as it is written in the code, without the body. */
    private static function signature(ReflectionMethod $method): string
    {
        $params = [];

        foreach ($method->getParameters() as $param) {
            $type = $param->getType();

            $one = $type ? self::typeName($type) . ' ' : '';
            $one .= '$' . $param->getName();

            if ($param->isDefaultValueAvailable()) {
                $one .= ' = ' . self::value($param->getDefaultValue());
            }

            $params[] = $one;
        }

        $return = $method->getReturnType();

        return $method->getName() . '(' . implode(', ', $params) . ')'
            . ($return ? ': ' . self::typeName($return) : '');
    }

    /** A type as php writes it, nullable and union alike. */
    private static function typeName(\ReflectionType $type): string
    {
        if ($type instanceof ReflectionNamedType) {
            return ($type->allowsNull() && $type->getName() !== 'null' && $type->getName() !== 'mixed' ? '?' : '')
                . $type->getName();
        }

        return (string) $type;
    }

    /** A default value the way it is typed in the source. */
    private static function value(mixed $value): string
    {
        if (is_string($value)) {
            return "'" . $value . "'";
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        if (is_array($value)) {
            return '[]';
        }

        return (string) $value;
    }
}
