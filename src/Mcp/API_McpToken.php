<?php

namespace MagicProSrc\Mcp;

use Illuminate\Support\Facades\Auth;
use MagicProSrc\Api\AbstractApi;

/**
 * The token of the http МСП from the side of the admin panel:
 * POST /a_dmin/api/mcpToken.
 *
 * Three commands: what the state is, give a new one, take it away. The answer
 * shape is built by AbstractApi::run().
 *
 * The token is shown once, at the moment it is issued, and after that only its
 * clocks are known: the site keeps a hash. Lost — press the button again, the
 * previous one dies from that alone.
 */
class API_McpToken extends AbstractApi
{
    protected array $map = [
        'state'  => 'tokenState',
        'issue'  => 'tokenIssue',
        'revoke' => 'tokenRevoke',
    ];

    protected function tokenState(array $params): array
    {
        return Token::state($this->userId());
    }

    /**
     * A new token, and with it the address it will be tied to.
     *
     * The address is taken from this very request: the token is issued in the
     * admin panel from the same machine the agent will be started on, so the
     * browser tells us where the calls are going to come from.
     */
    protected function tokenIssue(array $params): array
    {
        $userId = $this->userId();

        $token = Token::issue($userId, (string) request()->ip());

        \MproHelper::addLog('mcp', [
            'issued' => $userId,
            'ip'     => (string) request()->ip(),
        ]);

        return [
            'token' => $token,
            'state' => Token::state($userId),
        ];
    }

    protected function tokenRevoke(array $params): array
    {
        $userId = $this->userId();

        $gone = Token::revoke($userId);

        if ($gone) {
            \MproHelper::addLog('mcp', ['revoked' => $userId]);
        }

        return ['revoked' => $gone, 'state' => Token::state($userId)];
    }

    /**
     * Whose token we are talking about.
     *
     * The route lets in anyone logged into the admin panel, and the token opens
     * every tool of МСП, including the ones that write php of an article — so
     * the section belongs to an administrator, like the page it lives on.
     */
    private function userId(): int
    {
        $user = Auth::guard('magic')->user();

        if (! $user) {
            throw new \Exception('MagicPro authorization required');
        }

        if ($user->role !== 'admin') {
            throw new \Exception('administrator rights required');
        }

        return (int) $user->id;
    }
}
