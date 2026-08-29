<?php

namespace MagicProSrc\Ai;

use Illuminate\Support\Facades\Auth;
use MagicProSrc\Api\AbstractApi;

/**
 * The AI agent of the admin panel: POST /a_dmin/api/mcp.
 *
 * Every command works "up to the first error" and throws; the answer shape is
 * built by AbstractApi::run().
 *
 * Two gates stand in front of the agent. The route lets in an administrator of
 * MagicPro, and the password of `configAI.php` is asked once more at the start:
 * the agent is a shell on the server, and being logged into the admin panel is
 * not enough for that.
 *
 * A started session is written into the browser session, and every later
 * command is checked against that list. So the password guards not only the
 * start: a session name that was never started here is not accepted, whoever
 * sends it.
 */
class API_Ai extends AbstractApi
{
    protected const ERRORS = [
        'auth_required'    => 'MagicPro authorization required',
        'password_missing' => 'password is not set in configAI.php',
        'password_wrong'   => 'wrong password',
        'session_foreign'  => 'unknown session',
        'command_empty'    => 'command required',
        'prompts_bad'      => 'prompts must be a list',
    ];

    /** Sessions started from this browser session. */
    private const SESSION_KEY = 'magicAiSessions';

    protected array $map = [
        'config' => 'configState',
        'start'  => 'startSession',
        'output' => 'sessionOutput',
        'reread' => 'sessionReread',
        'send'   => 'sendCommand',
        'close'  => 'closeSession',
        'killAll' => 'killSessions',
        'prompts' => 'promptList',
        'promptsSave' => 'promptSave',
    ];

    /**
     * What the screen may offer.
     *
     * Only labels go out: the command lines stay on the server, and the browser
     * chooses by number. The path of the settings goes with the answer because
     * the file is written by hand and the first question of an empty screen is
     * where it is supposed to lie.
     */
    protected function configState(array $params): array
    {
        if (! AiConfig::ready()) {
            return [
                'ready' => false,
                'path'  => AiConfig::path(),
            ];
        }

        $agents = [];

        foreach (AiConfig::agents() as $agent) {
            $agents[] = [
                'name'  => $agent['name'],
                'start' => array_column($agent['start'], 'label'),
                'exit'  => array_column($agent['exit'], 'label'),
                // правила агента: без них запуск не даётся, и сказать об этом
                // надо до нажатия, а не отказом после
                'rules' => AiSession::hasRules($agent),
                'rulesPath' => AiSession::rulesFile($agent),
            ];
        }

        return [
            'ready'        => true,
            // пароль сам по себе наружу не едет, едет только «он задан»: пустой
            // пароль — это выключенный раздел, и сказать об этом надо на экране
            'password'     => AiConfig::password() !== '',
            'path'         => AiConfig::path(),
            'agents'       => $agents,
            'pollInterval' => AiConfig::pollInterval(),
        ];
    }

    protected function startSession(array $params): array
    {
        $user = Auth::guard('magic')->user();

        if (! $user) {
            throw new \Exception(self::ERRORS['auth_required']);
        }

        $password = AiConfig::password();

        if ($password === '') {
            throw new \Exception(self::ERRORS['password_missing']);
        }

        if (! hash_equals($password, (string) ($params['password'] ?? ''))) {
            throw new \Exception(self::ERRORS['password_wrong']);
        }

        // command_index, а не command: слово command занято именем самой
        // команды API, и номер строки запуска пришлось бы искать в ней
        $instance = AiSession::start(
            (int) ($params['agent'] ?? 0),
            (int) ($params['command_index'] ?? 0),
            (int) $user->id,
        );

        self::remember($instance);

        return [
            'instance'     => $instance,
            'pollInterval' => AiConfig::pollInterval(),
        ];
    }

    protected function sessionOutput(array $params): array
    {
        return AiSession::output(self::instance($params));
    }

    protected function sessionReread(array $params): array
    {
        return AiSession::reread(self::instance($params));
    }

    protected function sendCommand(array $params): array
    {
        $instance = self::instance($params);

        $command = (string) ($params['text'] ?? '');

        if (trim($command) === '') {
            throw new \Exception(self::ERRORS['command_empty']);
        }

        AiSession::send($instance, $command);

        return ['ok' => true];
    }

    protected function closeSession(array $params): array
    {
        $instance = self::instance($params);

        return ['alive' => AiSession::close($instance, (int) ($params['command_index'] ?? 0))];
    }

    /**
     * Kills every session of the one who asks.
     *
     * Not guarded by the list of the browser session on purpose: it is asked
     * for exactly when that list is of no help — the tab that started the
     * session is closed, and the agent is still holding on to something.
     * Killing reaches no further than the sessions of this administrator.
     */
    protected function killSessions(array $params): array
    {
        $user = Auth::guard('magic')->user();

        if (! $user) {
            throw new \Exception(self::ERRORS['auth_required']);
        }

        return ['killed' => AiSession::killUser((int) $user->id)];
    }

    /**
     * Prompts of the section: the list and the whole list back.
     *
     * No password here and no check against the browser session: this is text
     * for the operator, not a way to the agent. Getting in as an administrator
     * is enough to read it, and to write it.
     */
    protected function promptList(array $params): array
    {
        return ['prompts' => AiPrompts::all()];
    }

    protected function promptSave(array $params): array
    {
        $list = $params['prompts'] ?? [];

        if (! is_array($list)) {
            throw new \Exception(self::ERRORS['prompts_bad']);
        }

        return ['prompts' => AiPrompts::save($list)];
    }

    /** The session of the request, if it was started from this browser. */
    private static function instance(array $params): string
    {
        $instance = (string) ($params['instance'] ?? '');

        if ($instance === '' || ! in_array($instance, self::started(), true)) {
            throw new \Exception(self::ERRORS['session_foreign']);
        }

        return $instance;
    }

    private static function started(): array
    {
        return (array) session(self::SESSION_KEY, []);
    }

    private static function remember(string $instance): void
    {
        $started = self::started();

        $started[] = $instance;

        session([self::SESSION_KEY => $started]);
    }
}
