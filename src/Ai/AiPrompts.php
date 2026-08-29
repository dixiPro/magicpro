<?php

namespace MagicProSrc\Ai;

use Illuminate\Support\Facades\File;

/**
 * Prompts of the MCP section: `storage/app/private/ai-sessions/commands.json`.
 *
 * A prompt is a piece of text somebody types into the agent often enough to be
 * tired of typing it. The list is edited from the admin panel and lives next to
 * the settings of the section.
 *
 * json, not php, although the settings next to it are php: this file is written
 * by the browser, and a php file written by the browser is code the site would
 * then execute. json cannot be executed, and that is the whole reason.
 *
 * The list is common to the site, not to the administrator: there are few of
 * them, and a good prompt is worth more shared than hidden.
 */
class AiPrompts
{
    private const FILE = AiConfig::DIR . '/commands.json';

    /** Limits, so that a broken screen cannot leave an unreadable file. */
    private const MAX = 200;

    private const NAME_MAX = 120;

    private const TEXT_MAX = 20000;

    public static function path(): string
    {
        return storage_path(self::FILE);
    }

    /**
     * The list as it is stored.
     *
     * No file, broken json, wrong shape — an empty list. The section has to
     * open even when the file was edited by hand into nonsense.
     *
     * @return array<int, array{name: string, text: string}>
     */
    public static function all(): array
    {
        if (! is_file(self::path())) {
            return [];
        }

        $list = json_decode((string) file_get_contents(self::path()), true);

        if (! is_array($list)) {
            return [];
        }

        return self::clean($list);
    }

    /**
     * Writes the list as it came from the screen.
     *
     * The whole list at once, not one prompt at a time: the screen edits it as
     * a whole and knows what it wants to see, and a file of two hundred lines
     * is written faster than a request per line.
     *
     * @return array<int, array{name: string, text: string}> what was actually stored
     */
    public static function save(array $list): array
    {
        $clean = self::clean($list);

        File::ensureDirectoryExists(dirname(self::path()));

        File::put(
            self::path(),
            json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );

        return $clean;
    }

    /**
     * Rows that are worth keeping.
     *
     * A row without a name or without text is not a prompt but a line somebody
     * added and never filled in — it goes out silently, the way an empty row of
     * any editor does.
     */
    private static function clean(array $list): array
    {
        $clean = [];

        foreach ($list as $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));
            $text = trim((string) ($row['text'] ?? ''));

            if ($name === '' || $text === '') {
                continue;
            }

            $clean[] = [
                'name' => mb_substr($name, 0, self::NAME_MAX),
                'text' => mb_substr($text, 0, self::TEXT_MAX),
            ];

            if (count($clean) >= self::MAX) {
                break;
            }
        }

        return $clean;
    }
}
