<?php

namespace MagicProSrc\Mcp;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The only gate of the http МСП.
 *
 * Behind it stands the same server with the same tools that the local one
 * runs, so everything the agent is allowed on a live site is decided here.
 *
 * The section stays shut until somebody presses "get a token" in the admin
 * panel: no file — no answer but 401, to everybody and always.
 */
class TokenMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $bearer = $this->bearer($request);

        try {
            $userId = Token::verify($bearer, (string) $request->ip());
        } catch (\Throwable $e) {
            // без токена в лог не пишется: адрес открыт всему интернету, и
            // каждый сканер, постучавший мимо, раздувал бы лог. Интересен
            // отказ тому, кто токен предъявил, — просроченный, чужой, с
            // другого адреса
            if ($bearer !== '') {
                McpLog::denied($request, $e->getMessage());
            }

            return response()->json(['error' => $e->getMessage()], 401);
        }

        // a token is not a right of its own: it names a person, and the rights
        // are his. Deleted, or no longer an administrator — the token stops
        // working at once, without waiting for its hour to run out
        $user = \MagicProDatabaseModels\MagicProUser::find($userId);

        if (! $user || $user->role !== 'admin') {
            McpLog::denied($request, 'the owner of the token is gone or is not an administrator');

            return response()->json(['error' => 'the token no longer has rights'], 403);
        }

        // the hour is counted from the last call, so the mark moves before the
        // work and not after it: a tool that throws still proves the agent is
        // there
        Token::touch($userId);

        McpLog::call($request, $userId);

        // the owner of the token becomes the user of this request, without a
        // session and only for this request. Everything behind the tools then
        // works as it does for a person in the browser — the archive of
        // versions, for one, writes down who saved
        Auth::guard('magic')->onceUsingId($userId);

        return $next($request);
    }

    private function bearer(Request $request): string
    {
        $header = (string) $request->header('Authorization', '');

        if (! preg_match('/^Bearer\s+(\S+)$/i', trim($header), $found)) {
            return '';
        }

        return $found[1];
    }
}
