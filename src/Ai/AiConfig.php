<?php

namespace MagicProSrc\Ai;

/**
 * Settings of the AI agent: `storage/app/private/ai-sessions/configAI.php`.
 *
 * The installation puts the file there, copying the one that comes with the
 * package, and from then on the site edits its own copy. The password in the
 * copy is empty, and an empty password forbids starting anything: the section
 * arrives switched off, and switching it on is a deliberate act.
 *
 * The file is not validated beyond its shape. Whether the agent is installed,
 * whether its MCP is registered and what its rules allow is the business of the
 * one who wrote it.
 */
class AiConfig
{
    /** Everything of the section lives here: the settings and both logs. */
    public const DIR = 'app/private/ai-sessions';

    private const FILE = 'configAI.php';

    /** What the file may leave out. */
    private const POLL_INTERVAL = 1000;

    private const SESSION_TIMEOUT = 600;

    private const KILL_TIMEOUT = 720;

    /** The poll may not be faster than this: every poll is a request. */
    private const POLL_MIN = 200;

    private static ?array $config = null;

    public static function path(): string
    {
        return storage_path(self::DIR . '/' . self::FILE);
    }

    public static function ready(): bool
    {
        return is_file(self::path());
    }

    /**
     * The copy that comes with the package.
     *
     * The site never starts without settings: the installation copies this file
     * over when there is none, and from then on the site edits its own copy.
     * The password in it is empty, so the section arrives switched off.
     */
    public static function template(): string
    {
        return __DIR__ . '/../Config/configAI.php';
    }

    /**
     * Is the section allowed to work at all.
     *
     * An empty password is not a setting nobody got to — it is the switch. The
     * section hands out a shell on the server, and it stays shut until somebody
     * says otherwise in the settings of this site.
     */
    public static function on(): bool
    {
        return self::ready() && self::password() !== '';
    }

    /** The file as it is written, read once per request. */
    public static function all(): array
    {
        if (self::$config !== null) {
            return self::$config;
        }

        if (! self::ready()) {
            throw new \Exception('configAI.php not found: ' . self::path());
        }

        $config = require self::path();

        if (! is_array($config)) {
            throw new \Exception('configAI.php must return an array');
        }

        return self::$config = $config;
    }

    /**
     * Agents of the file, in the order they are written.
     *
     * The screen picks an agent by its number in this list, so the order is the
     * agreement between the file and the front: names never travel from the
     * browser, only indexes.
     *
     * @return array<int, array{name: string, start: array, exit: array}>
     */
    public static function agents(): array
    {
        $all = self::all();

        $raw = $all['agents'] ?? $all['agent'] ?? [];

        // one agent is written straight, several — as a list
        $list = isset($raw['name']) ? [$raw] : $raw;

        $agents = [];

        foreach ((array) $list as $agent) {
            if (! is_array($agent)) {
                continue;
            }

            $agents[] = [
                'name'  => self::safeName((string) ($agent['name'] ?? '')),
                'start' => self::commands($agent['startCommands'] ?? []),
                'exit'  => self::commands($agent['exitCommands'] ?? []),
                'cwd'   => trim((string) ($agent['cwd'] ?? '')),
                'env'   => self::env($agent['env'] ?? []),
            ];
        }

        return $agents;
    }

    public static function agent(int $index): array
    {
        $agents = self::agents();

        if (! isset($agents[$index])) {
            throw new \Exception('agent not found: ' . $index);
        }

        if ($agents[$index]['name'] === '') {
            throw new \Exception('agent has no name: ' . $index);
        }

        return $agents[$index];
    }

    /**
     * The agent a session was started with, found by the name it begins with.
     *
     * The name of the session carries the agent, and closing needs its exit
     * command — including the closing that cron does, where nobody is left to
     * ask.
     */
    public static function agentByInstance(string $instance): ?array
    {
        foreach (self::agents() as $agent) {
            if ($agent['name'] !== '' && str_starts_with($instance, $agent['name'])) {
                return $agent;
            }
        }

        return null;
    }

    public static function password(): string
    {
        return (string) (self::all()['password'] ?? '');
    }

    public static function pollInterval(): int
    {
        return max(self::POLL_MIN, (int) (self::all()['poll_interval'] ?? self::POLL_INTERVAL));
    }

    public static function sessionTimeout(): int
    {
        return max(1, (int) (self::all()['session_timeout'] ?? self::SESSION_TIMEOUT));
    }

    public static function killTimeout(): int
    {
        return max(1, (int) (self::all()['session_kill_timeout'] ?? self::KILL_TIMEOUT));
    }

    /**
     * Commands of one list as label and command line.
     *
     * They are written as a list of one-pair arrays, `['Заново' => 'codex']`,
     * and a plain `'Заново' => 'codex'` map is read the same way: the file is
     * typed by hand, and both shapes look right to the one typing it.
     *
     * @return array<int, array{label: string, command: string}>
     */
    private static function commands(mixed $raw): array
    {
        $list = [];

        foreach ((array) $raw as $key => $item) {
            if (is_array($item)) {
                foreach ($item as $label => $command) {
                    $list[] = [
                        'label'   => (string) $label,
                        'command' => (string) $command,
                    ];
                }

                continue;
            }

            $list[] = [
                'label'   => is_string($key) ? $key : (string) $item,
                'command' => (string) $item,
            ];
        }

        return $list;
    }

    /**
     * Environment of the agent.
     *
     * The name of a variable becomes an argument of tmux as it is written, so
     * only what may be a name is let through: a letter or an underscore, then
     * letters, digits and underscores. The value is escaped when the command is
     * built, and may be anything.
     *
     * @return array<string, string>
     */
    private static function env(mixed $raw): array
    {
        $env = [];

        foreach ((array) $raw as $name => $value) {
            if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', (string) $name) === 1) {
                $env[(string) $name] = (string) $value;
            }
        }

        return $env;
    }

    /** The name goes into the name of a tmux session, so nothing but letters. */
    private static function safeName(string $name): string
    {
        return (string) preg_replace('/[^A-Za-z0-9]/', '', $name);
    }
}
