<?php

namespace MagicProSrc\Cleanup;

use Illuminate\Support\Facades\File;
use MagicProSrc\MagicLang;

/**
 * The html report of a repair run.
 *
 * A file and not a log line: it is read a month later, by a human, who then
 * goes and corrects by hand what the repair did roughly. Three of them are
 * kept — the fourth pushes out the oldest.
 *
 * The folder is named after the table. A second table one day will lie next to
 * this one with a folder of its own.
 *
 * The language is the language of the admin panel at the moment of the run:
 * the report is built from the same dictionary the pages are.
 */
class CleanupReport
{
    /** Under storage/. */
    private const DIR = 'app/private/magic/dataTableCleanup/article';

    /** How many reports are kept. */
    private const KEEP = 3;

    /** The only names this class works with. */
    private const MASK = '/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}\.html$/';

    public static function dir(): string
    {
        return storage_path(self::DIR);
    }

    /**
     * Writes the report and answers with its name.
     *
     * @param  array<int, array<int, array{id: ?int, name: string, was: string, now: string}>>  $rows
     */
    public static function write(array $rows): string
    {
        File::ensureDirectoryExists(self::dir());

        $file = date('Y-m-d_H-i') . '.html';

        file_put_contents(self::dir() . '/' . $file, self::html($rows));
        @chmod(self::dir() . '/' . $file, 0640);

        self::rotate();

        return $file;
    }

    /**
     * The reports there are, the newest on top.
     *
     * @return array<int, array{file: string, date: string}>
     */
    public static function all(): array
    {
        if (! is_dir(self::dir())) {
            return [];
        }

        $files = [];

        foreach (scandir(self::dir()) ?: [] as $file) {
            if (preg_match(self::MASK, $file)) {
                $files[] = $file;
            }
        }

        rsort($files);

        return array_map(fn ($file) => [
            'file' => $file,
            'date' => str_replace('_', ' ', substr($file, 0, -5)),
        ], $files);
    }

    /** The path of a report, or an exception: the name comes from the browser. */
    public static function path(string $file): string
    {
        if (! preg_match(self::MASK, $file)) {
            throw new \Exception('bad report name: ' . $file);
        }

        $path = self::dir() . '/' . $file;

        if (! is_file($path)) {
            throw new \Exception('report not found: ' . $file);
        }

        return $path;
    }

    private static function rotate(): void
    {
        foreach (array_slice(self::all(), self::KEEP) as $row) {
            @unlink(self::dir() . '/' . $row['file']);
        }
    }

    /** @param array<int, array<int, array>> $rows */
    private static function html(array $rows): string
    {
        $msg = fn (string $key) => e(MagicLang::getMsg($key));

        $found = array_sum(array_map('count', $rows));

        $html = '<!doctype html><html><head><meta charset="utf-8">'
            . '<title>' . $msg('cleanup_title') . ' ' . date('Y-m-d H:i') . '</title>'
            . '<style>'
            . 'body{font:14px/1.4 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;margin:2rem;color:#222}'
            . 'h1{font-size:1.3rem}h2{font-size:1rem;margin-top:2rem}'
            . 'table{border-collapse:collapse;width:100%;margin-top:.5rem}'
            . 'th,td{border:1px solid #ddd;padding:.3rem .5rem;text-align:left;vertical-align:top}'
            . 'th{background:#f5f5f5;font-weight:600}'
            . '.loud{color:#a00}.muted{color:#777}'
            . '</style></head><body>';

        $html .= '<h1>' . $msg('cleanup_title') . '</h1>';
        $html .= '<p class="muted">' . $msg('cleanup_date') . ': ' . date('Y-m-d H:i')
            . '<br>' . $msg('cleanup_total') . ': ' . $found . '</p>';

        if (! $found) {
            return $html . '<p>' . $msg('cleanup_clean') . '</p></body></html>';
        }

        foreach (ArticleCheck::POINTS as $point => $key) {
            if (empty($rows[$point])) {
                continue;
            }

            $loud = in_array($point, ArticleCheck::LOUD, true);

            $html .= '<h2' . ($loud ? ' class="loud"' : '') . '>' . $point . '. ' . $msg($key)
                . ' — ' . count($rows[$point]) . '</h2>';

            if ($loud) {
                $html .= '<p class="loud">' . $msg('cleanup_warn_name') . '</p>';
            }

            $html .= '<table><tr><th>id</th><th>' . $msg('cleanup_col_name') . '</th>'
                . '<th>' . $msg('cleanup_col_was') . '</th>'
                . '<th>' . $msg('cleanup_col_now') . '</th></tr>';

            foreach ($rows[$point] as $row) {
                $html .= '<tr><td>' . e((string) ($row['id'] ?? '')) . '</td>'
                    . '<td>' . e($row['name']) . '</td>'
                    . '<td>' . e($row['was']) . '</td>'
                    . '<td>' . e($row['now']) . '</td></tr>';
            }

            $html .= '</table>';
        }

        return $html . '</body></html>';
    }
}
