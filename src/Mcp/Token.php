<?php

namespace MagicProSrc\Mcp;

/**
 * The token of the http МСП: `storage/app/private/magic/mcpTokens/<id>.php`.
 *
 * One user — one file, and the name of the file is the id of the user. Issuing
 * a new token overwrites the old one, so the same button is also the way to
 * revoke: there is never a second live token of the same person.
 *
 * The file keeps a hash, not the token. What was shown once in the admin panel
 * exists after that only in the memory of the agent process, and a copy of the
 * site directory gives nothing to whoever took it.
 *
 * Two clocks kill the token. An hour without calls — the working session is
 * over, whoever left the laptop open left nothing usable behind. And a day from
 * the issue, however busy it was: a token that is used every fifty minutes must
 * not live forever.
 */
class Token
{
    /** Under `storage/`, next to the rest of the private data of MagicPro. */
    public const DIR = 'app/private/magic/mcpTokens';

    /**
     * Between the id of the user and the random part of a token.
     *
     * The id in the open is not a secret: whoever holds the token holds
     * everything anyway, and this way the file is opened straight away instead
     * of comparing hashes with every file in the directory.
     */
    private const SEP = '___';

    /** An hour without calls. */
    private const IDLE = 3600;

    /** And a day from the issue in any case. */
    private const LIFETIME = 86400;

    public static function dir(): string
    {
        return storage_path(self::DIR);
    }

    public static function path(int $userId): string
    {
        return self::dir() . '/' . $userId . '.php';
    }

    /**
     * A new token for the user, the old one dies here.
     *
     * The plain token is returned once and never stored: from here it goes to
     * the screen and nowhere else.
     */
    public static function issue(int $userId, string $ip): string
    {
        $token = $userId . self::SEP . bin2hex(random_bytes(32));
        $now   = time();

        self::write($userId, [
            'hash'         => hash('sha256', $token),
            'ip'           => $ip,
            'created_at'   => $now,
            'last_used_at' => $now,
        ]);

        return $token;
    }

    /**
     * Whose token this is, or an exception saying why it is not accepted.
     *
     * The token begins with the id of its owner, so the file is known at once.
     * The hash is compared with `hash_equals`: a plain `==` tells by its timing
     * how much of the hash matched.
     *
     * The reason of a refusal goes out as it is. The agent is not an attacker
     * at a login form, it is a program that must understand whether to ask for
     * a new token or to stop.
     */
    public static function verify(string $token, string $ip): int
    {
        if ($token === '') {
            throw new \Exception('no token: send it as "Authorization: Bearer <token>"');
        }

        [$userId, $rest] = array_pad(explode(self::SEP, $token, 2), 2, '');

        if (! ctype_digit($userId) || $rest === '') {
            throw new \Exception('malformed token');
        }

        $userId = (int) $userId;

        $data = self::read($userId);

        if ($data === null || ! hash_equals((string) ($data['hash'] ?? ''), hash('sha256', $token))) {
            throw new \Exception('unknown token: it was revoked or replaced by a newer one');
        }

        if ((string) ($data['ip'] ?? '') !== $ip) {
            throw new \Exception('the token was issued for another address');
        }

        $now = time();

        if ($now - (int) ($data['last_used_at'] ?? 0) > self::IDLE) {
            throw new \Exception('the token expired: an hour without calls. Take a new one in the admin panel');
        }

        if ($now - (int) ($data['created_at'] ?? 0) > self::LIFETIME) {
            throw new \Exception('the token expired: a day from the issue. Take a new one in the admin panel');
        }

        return $userId;
    }

    /** The hour is counted from here: every accepted call moves the mark. */
    public static function touch(int $userId): void
    {
        $data = self::read($userId);

        if ($data === null) {
            return;
        }

        $data['last_used_at'] = time();

        self::write($userId, $data);
    }

    public static function revoke(int $userId): bool
    {
        $path = self::path($userId);

        if (! is_file($path)) {
            return false;
        }

        self::invalidate($path);

        return (bool) @unlink($path);
    }

    /**
     * What the admin panel shows: is there a token, from where, until when.
     *
     * Nothing of the token itself gets out, only its clocks. `alive` is the
     * same pair of checks the middleware makes, so the screen never claims a
     * token that would be refused on the first call.
     */
    public static function state(int $userId): array
    {
        $data = self::read($userId);

        if ($data === null) {
            return ['exists' => false];
        }

        $now     = time();
        $idle    = self::IDLE - ($now - (int) $data['last_used_at']);
        $lasting = self::LIFETIME - ($now - (int) $data['created_at']);

        return [
            'exists'       => true,
            'ip'           => (string) $data['ip'],
            'created_at'   => date('Y-m-d H:i', (int) $data['created_at']),
            'last_used_at' => date('Y-m-d H:i', (int) $data['last_used_at']),
            'alive'        => $idle > 0 && $lasting > 0,
            // minutes, rounded down: the screen has no business with seconds
            'idle_left'    => (int) floor(max(0, $idle) / 60),
            'life_left'    => (int) floor(max(0, $lasting) / 60),
        ];
    }

    private static function read(int $userId): ?array
    {
        $path = self::path($userId);

        if (! is_file($path)) {
            return null;
        }

        $data = include $path;

        return is_array($data) ? $data : null;
    }

    /**
     * The file is rewritten on every accepted call, so the cache of the
     * compiled php is dropped right here: opcache would otherwise keep serving
     * the previous contents for a couple of seconds, and a token issued a
     * moment ago would be refused as unknown.
     */
    private static function write(int $userId, array $data): void
    {
        $dir = self::dir();

        if (! is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }

        $path = self::path($userId);

        $text = "<?php\n\n"
            . "// MagicPro МСП token. Written by the admin panel, not by hand.\n\n"
            . 'return ' . var_export($data, true) . ";\n";

        file_put_contents($path, $text, LOCK_EX);
        @chmod($path, 0600);

        self::invalidate($path);
    }

    private static function invalidate(string $path): void
    {
        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($path, true);
        }
    }
}
