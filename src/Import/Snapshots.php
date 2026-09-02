<?php

namespace MagicProSrc\Import;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use MagicProAdminControllers\ImportExportController;

/**
 * Saved states of the article tree.
 *
 * A snapshot is the export as it is — same call, same file shape — so a
 * snapshot and a hand-made export file are the same thing, and either of them
 * can be imported back.
 *
 * The name is offered by the page — `root_` and the date to the minute — and
 * rewritten by the one who saves: half a year later «root_2026-01-30_18-36» says
 * when, and «before_the_shop» says what for. Whatever is typed, only latin
 * letters, digits, dash and underscore reach the disk: the name comes from the
 * browser and turns into a path.
 *
 * Nothing here cleans up: how many states to keep is the business of the one
 * who makes them.
 */
class Snapshots
{
    /** Under storage/, next to the other private files of the site. */
    private const DIR = 'app/private/magic/articles';

    /** The only names this class works with. Anything else is not ours. */
    private const MASK = '/^[A-Za-z0-9_-]{1,80}\.json$/';

    public static function dir(): string
    {
        return storage_path(self::DIR);
    }

    /** Writes the state of the whole tree and answers with the file name. */
    public static function save(string $name = ''): string
    {
        File::ensureDirectoryExists(self::dir());

        $json = app(ImportExportController::class)
            ->exportArticle(new Request(['id' => 1]))
            ->getContent();

        $file = self::fileName($name);

        file_put_contents(self::dir() . '/' . $file, $json);
        @chmod(self::dir() . '/' . $file, 0640);

        return $file;
    }

    /** What the page offers when nothing is typed. */
    public static function stamp(): string
    {
        return 'root_' . date('Y-m-d_H-i');
    }

    /**
     * A typed name turned into a file name.
     *
     * Everything but latin letters, digits, dash and underscore is dropped
     * rather than refused: the name is a label for a human, and a russian word
     * or a space in it is a mistake to be swallowed, not an error to argue
     * about. What is left of an empty name is the offered one.
     */
    private static function fileName(string $name): string
    {
        $name = trim($name);

        if (str_ends_with(strtolower($name), '.json')) {
            $name = substr($name, 0, -5);
        }

        $name = substr((string) preg_replace('/[^A-Za-z0-9_-]/', '', $name), 0, 80);

        return ($name !== '' ? $name : self::stamp()) . '.json';
    }

    /**
     * The states there are, the newest on top.
     *
     * @return array<int, array{file: string, size: int, time: int, date: string}>
     */
    public static function all(): array
    {
        if (! is_dir(self::dir())) {
            return [];
        }

        $rows = [];

        foreach (scandir(self::dir()) ?: [] as $file) {
            if (! preg_match(self::MASK, $file)) {
                continue;
            }

            $path = self::dir() . '/' . $file;

            $rows[] = [
                'file' => $file,
                'size' => (int) filesize($path),
                'time' => (int) filemtime($path),
                'date' => date('Y-m-d H:i', (int) filemtime($path)),
            ];
        }

        // By time, not by name: the names are whatever was typed.
        usort($rows, fn ($a, $b) => $b['time'] <=> $a['time']);

        return $rows;
    }

    /** The path of a state, or an exception: the name comes from the browser. */
    public static function path(string $file): string
    {
        if (! preg_match(self::MASK, $file)) {
            throw new \Exception('bad snapshot name: ' . $file);
        }

        $path = self::dir() . '/' . $file;

        if (! is_file($path)) {
            throw new \Exception('snapshot not found: ' . $file);
        }

        return $path;
    }

    public static function read(string $file): string
    {
        return (string) file_get_contents(self::path($file));
    }

    public static function delete(string $file): void
    {
        unlink(self::path($file));
    }
}
