<?php

namespace MagicProSrc\Mcp;

use Illuminate\Http\Request;

/**
 * The log of the http МСП: `storage/logs/mcp.log`, fourteen days back.
 *
 * On a live site this is the only way to find out afterwards what the agent
 * did. Written by the middleware, so both the accepted calls and the refused
 * ones get in.
 *
 * The token never goes into the log, not even in pieces: a log is read by more
 * people than a settings file.
 */
class McpLog
{
    private const NAME = 'mcp';

    public static function call(Request $request, int $userId): void
    {
        \MproHelper::addLog(self::NAME, [
            'user' => $userId,
            'ip'   => (string) $request->ip(),
        ] + self::what($request));
    }

    public static function denied(Request $request, string $reason): void
    {
        \MproHelper::addLog(self::NAME, [
            'denied' => $reason,
            'ip'     => (string) $request->ip(),
        ] + self::what($request));
    }

    /**
     * The jsonrpc method and, for a call of a tool, its name.
     *
     * The arguments stay out: whole articles and blades travel in them, and a
     * log that eats the body of every save is a log nobody reads.
     */
    private static function what(Request $request): array
    {
        $method = self::field($request->json('method', ''));
        $tool   = self::field($request->json('params.name', ''));

        $what = ['method' => $method !== '' ? $method : '-'];

        if ($tool !== '') {
            $what['tool'] = $tool;
        }

        return $what;
    }

    /**
     * One line from the body, as it came from outside: not a string — empty,
     * no line breaks, not longer than a name can be. `(string)` of an array
     * sent in place of a name raised a warning, and the 401 became a 500.
     */
    private static function field(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        return mb_substr(trim((string) preg_replace('/\s+/u', ' ', $value)), 0, 100);
    }
}
