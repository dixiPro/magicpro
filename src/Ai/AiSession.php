<?php

namespace MagicProSrc\Ai;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

/**
 * A session of work with an AI agent.
 *
 * The agent is a terminal program and lives inside tmux: it wants a terminal,
 * it runs longer than any request, and it has to survive between two polls of
 * the browser. The session of tmux is that life.
 *
 * The screen is asked of tmux, not assembled here. An agent does not print
 * lines, it moves the cursor and redraws its window, and a recording of that
 * stream is not text at all — it is instructions to a terminal. tmux is the
 * terminal that carries them out, so `capture-pane` gives the finished picture,
 * already laid out in lines.
 *
 * One file stands for the session: `poll/<instance>.log`, touched on every
 * request of the browser for the screen. It is the sign that somebody is still
 * watching — an agent whose poll file went cold is a session nobody closed —
 * and the list of sessions the cleanup goes through.
 *
 * The name of the session goes to the browser and comes back with every
 * request, so it is checked on the way in: it becomes an argument of a shell
 * command and a name of a file.
 */
class AiSession
{
    private const POLL_DIR = AiConfig::DIR . '/poll';

    /**
     * The terminal the agent draws itself in. A detached session of tmux is
     * 80x24, and an agent lays its window out to that width whether anybody
     * looks at it or not.
     */
    private const WIDTH = 200;

    private const HEIGHT = 50;

    /** How much of what scrolled above the screen tmux keeps. */
    private const HISTORY = 10000;

    /** How much of it goes to the browser on every poll. */
    private const LIVE_LINES = 200;

    /**
     * Seconds the pane lives after the agent left.
     *
     * tmux reads the pane in its event loop and lets go of what is left in it
     * when the process ends: an agent that says one word and exits says it into
     * nowhere — the pane is dead and empty. A second of waiting after the
     * command is enough for the last screen to be read and kept.
     */
    private const HOLD = 1;

    /** Log of cron cleaning up after sessions nobody closed. */
    public const LOG = 'aiSession';

    /** How much of the screen goes into the log with a session it touched. */
    private const TAIL_LINES = 5;

    private const TAIL_CHARS = 400;

    /**
     * The rules of the agent: the file it reads in its working directory.
     *
     * Read by the agent itself, not by us — what it says is between the agent
     * and whoever wrote it. Our business is only that it exists: an agent
     * without rules is an agent allowed everything, and it is given a shell on
     * the server.
     */
    public const RULES = 'AGENTS.md';

    /**
     * Starts the agent and returns the name of the session.
     *
     * The command is taken from the settings by its number: what the browser
     * sends is an index, never a command line.
     */
    public static function start(int $agentIndex, int $commandIndex, int $userId): string
    {
        $agent = AiConfig::agent($agentIndex);

        if (! isset($agent['start'][$commandIndex])) {
            throw new \Exception('start command not found: ' . $commandIndex);
        }

        if (! self::hasRules($agent)) {
            throw new \Exception(self::RULES . ' not found: ' . self::rulesFile($agent));
        }

        // agent-id-time: имя должно само говорить, чьё оно. Без разделителей
        // «codex» + «11» + «788…» и «codex» + «1» + «1788…» — одна и та же
        // строка, и кнопка «завершить мои сеансы» не знала бы, что чьё
        $instance = $agent['name'] . '-' . $userId . '-' . time();

        File::ensureDirectoryExists(storage_path(self::POLL_DIR));

        $started = Process::run(sprintf(
            // Everything in one call to tmux, and in this order.
            //
            // The options go before new-session because a pane reads them when
            // it is created, and they cannot be set by an earlier call of their
            // own: a tmux server with no sessions does not live, and whatever
            // was put on it dies before the pane exists.
            //
            // alternate-screen off is what gives the session a history at all.
            // The agent draws itself the way vim does — it switches to the
            // alternate screen, and that screen has no scrollback whatsoever:
            // what leaves its top edge exists nowhere. Forbidden, the output
            // stays on the normal screen and scrolls into the history.
            //
            // The command goes as separate arguments, through an explicit
            // /bin/sh. tmux takes its default-shell from passwd, and php works
            // as www-data, whose shell is /usr/sbin/nologin: given as one
            // string, the command is never started at all and the session dies
            // in the same second. It is followed by a wait, see HOLD: the pane
            // has to outlive the last thing written into it.
            //
            // remain-on-exit comes last but still in the same call: an agent
            // that falls on its first line falls before a second call could
            // reach it, and with the option already on, its pane stays — with
            // the complaint still on it.
            'tmux set-option -wg alternate-screen off \; set-option -g history-limit %d'
                . ' \; new-session -d -s %s -c %s%s -x %d -y %d /bin/sh -c %s'
                . ' \; set-option -t %s remain-on-exit on',
            self::HISTORY,
            escapeshellarg($instance),
            escapeshellarg($agent['cwd'] !== '' ? $agent['cwd'] : base_path()),
            self::env($agent['env']),
            self::WIDTH,
            self::HEIGHT,
            escapeshellarg($agent['start'][$commandIndex]['command'] . '; sleep ' . self::HOLD),
            escapeshellarg($instance),
        ));

        if (! $started->successful()) {
            throw new \Exception('tmux: ' . trim($started->errorOutput() . ' ' . $started->output()));
        }

        self::poll($instance);

        return $instance;
    }

    /**
     * The screen of the agent as it is right now.
     *
     * The whole screen every time, not what was added: the agent redraws its
     * window instead of printing lines, so there is no "added" — there is a
     * picture, and it is the answer.
     *
     * A session that is over is finished right here: the screen is already
     * read, and there is nothing more to wait for.
     *
     * @return array{output: string, status: string, error: string}
     */
    public static function output(string $instance): array
    {
        self::checkName($instance);

        $alive = self::alive($instance);

        $screen = self::capture($instance, self::LIVE_LINES);

        if ($alive) {
            self::poll($instance);
        } else {
            self::finish($instance);
        }

        return [
            'output' => $screen['text'],
            'status' => $alive ? 'running' : 'stopped',
            'error'  => $alive ? '' : $screen['error'],
        ];
    }

    /**
     * The screen together with everything that scrolled above it.
     *
     * The window of the agent holds fifty lines, and a long answer leaves it
     * upwards. tmux keeps what left in the history of the pane, and this is the
     * way to it.
     *
     * @return array{output: string, status: string, error: string}
     */
    public static function reread(string $instance): array
    {
        self::checkName($instance);

        $alive = self::alive($instance);

        $screen = self::capture($instance, 0);

        if ($alive) {
            self::poll($instance);
        }

        return [
            'output' => $screen['text'],
            'status' => $alive ? 'running' : 'stopped',
            'error'  => $alive ? '' : $screen['error'],
        ];
    }

    /**
     * Types the command into the agent.
     *
     * The text goes with `-l`, literally: whatever is typed must reach the
     * agent as it was typed, and without `-l` tmux would read `Enter` or `C-c`
     * inside it as keys. Enter is a separate call for the same reason.
     */
    public static function send(string $instance, string $text): void
    {
        self::checkName($instance);

        if (! self::alive($instance)) {
            throw new \Exception('session is not running: ' . $instance);
        }

        Process::run(
            'tmux send-keys -l -t ' . escapeshellarg($instance) . ' ' . escapeshellarg($text)
        );

        Process::run(
            'tmux send-keys -t ' . escapeshellarg($instance) . ' Enter'
        );
    }

    /**
     * Closes the session the polite way: the agent is asked to leave.
     *
     * The agent is a program with its own state, and a killed process leaves
     * that state as it was in the middle of a thought. So the exit command of
     * the settings goes in, and the agent ends by itself; the session is let go
     * on the next look, when its pane is dead.
     *
     * Returns true while the agent was still running.
     */
    public static function close(string $instance, int $commandIndex = 0): bool
    {
        self::checkName($instance);

        if (! self::alive($instance)) {
            self::finish($instance);

            return false;
        }

        $agent = AiConfig::agentByInstance($instance);

        $exit = $agent['exit'][$commandIndex] ?? $agent['exit'][0] ?? null;

        if ($exit === null) {
            throw new \Exception('no exit command for: ' . $instance);
        }

        self::send($instance, $exit['command']);

        return true;
    }

    /**
     * Kills the session.
     *
     * The last resort of the cleanup: the agent was asked to leave and did not.
     */
    public static function kill(string $instance): void
    {
        self::checkName($instance);

        self::finish($instance);
    }

    /**
     * Is the agent still running.
     *
     * Two ways to be over, and both are asked about at once. The session may be
     * gone — then tmux answers with an error. Or the agent left and its pane
     * stayed, kept by remain-on-exit: the session is there, `pane_dead` is 1,
     * and the last screen is still readable.
     */
    public static function alive(string $instance): bool
    {
        self::checkName($instance);

        $run = Process::run(
            'tmux display-message -p -t ' . escapeshellarg($instance) . ' ' . escapeshellarg('#{pane_dead}')
        );

        return $run->successful() && trim($run->output()) !== '1';
    }

    /**
     * Sessions nobody watches any more, once a minute from the scheduler.
     *
     * The browser is the only one who closes a session properly, and a browser
     * is closed, reloaded and left. The poll file says when it was last seen:
     * gone quiet for `session_timeout` — the agent is asked to leave, gone
     * quiet for `session_kill_timeout` — the session is killed. An agent left
     * alone holds a process and, worse, an open MCP.
     *
     * Every session it touches goes into the log: this is the one thing here
     * that happens with nobody watching.
     *
     * A session that ended by itself is let go here too: closing one that is
     * already over is exactly what removes it.
     */
    public static function cleanup(): void
    {
        if (! AiConfig::ready()) {
            return;
        }

        $close = AiConfig::sessionTimeout();
        $kill  = AiConfig::killTimeout();

        foreach (glob(storage_path(self::POLL_DIR) . '/*.log') ?: [] as $file) {
            $instance = basename($file, '.log');

            try {
                self::checkName($instance);

                $idle = time() - (int) filemtime($file);

                if ($idle > $kill) {
                    // спрашиваем до убийства, и только ради записи: мёртвую
                    // пану мы просто прибираем, а «убил» в логе должно значить,
                    // что агента прервали на полуслове
                    $alive = self::alive($instance);

                    // экран снимается до убийства: агент не ушёл по команде, и
                    // объяснение этому может быть только на нём
                    $tail = $alive ? self::tail($instance) : '';

                    self::kill($instance);

                    $alive
                        ? self::note('kill', $instance, $idle, ['screen' => $tail])
                        : self::note('gone', $instance, $idle);

                    continue;
                }

                if ($idle > $close) {
                    // жив — ушла команда завершения, мёртв — сеанс просто убран
                    if (! self::close($instance)) {
                        self::note('gone', $instance, $idle);

                        continue;
                    }

                    self::note('close', $instance, $idle, [
                        // что именно ушло агенту и что у него было на экране,
                        // когда уходило: если он не выйдет, разбираться придётся
                        // по этим двум строчкам — больше не останется ничего
                        'sent'   => AiConfig::agentByInstance($instance)['exit'][0]['command'] ?? '',
                        'screen' => self::tail($instance),
                    ]);
                }
            } catch (\Throwable $e) {
                \MproHelper::addLog(self::LOG, [
                    'stage'    => 'cleanup',
                    'instance' => $instance,
                    'error'    => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Kills every session of one administrator.
     *
     * The way out of a session that got stuck: an agent holding a thread open
     * holds it for the next start too, and the browser knows only the sessions
     * of its own tab — a tab that has since been closed knows nothing at all.
     *
     * Sessions are looked for in two places at once, and for the same reason:
     * the poll file may be left without a session, the session without a poll
     * file. Both are junk, and both are meant to go.
     *
     * @return int how many were killed
     */
    public static function killUser(int $userId): int
    {
        $killed = 0;

        foreach (self::instances() as $instance) {
            // a session of another agent, of another administrator, or not ours
            // at all: tmux of this user may be running something else entirely
            if (self::owner($instance) !== $userId || AiConfig::agentByInstance($instance) === null) {
                continue;
            }

            self::kill($instance);

            $killed++;
        }

        return $killed;
    }

    /** Every session there is a sign of: a poll file, a session of tmux. */
    private static function instances(): array
    {
        $names = [];

        foreach (glob(storage_path(self::POLL_DIR) . '/*.log') ?: [] as $file) {
            $names[basename($file, '.log')] = true;
        }

        $run = Process::run('tmux list-sessions -F ' . escapeshellarg('#{session_name}'));

        if ($run->successful()) {
            foreach (preg_split('/\R/', trim($run->output())) ?: [] as $name) {
                if ($name !== '') {
                    $names[$name] = true;
                }
            }
        }

        return array_keys($names);
    }

    /**
     * The administrator a session belongs to, `0` if the name says nothing.
     *
     * The name is the only place that knows: there is no base, and the browser
     * session of one tab knows only what was started in it.
     */
    private static function owner(string $instance): int
    {
        $parts = explode('-', $instance);

        return count($parts) === 3 ? (int) $parts[1] : 0;
    }

    /**
     * A line in the log for every session the cleanup touched.
     *
     * Nobody is there to see it: the browser is gone, and the agent is asked to
     * leave or killed while nobody looks. The log is the only place where that
     * can be checked afterwards — and the only proof that the cleanup works at
     * all.
     */
    private static function note(string $stage, string $instance, int $idle, array $more = []): void
    {
        \MproHelper::addLog(self::LOG, [
            'stage'    => $stage,
            'instance' => $instance,
            'idle'     => $idle . 's',
        ] + $more);
    }

    /**
     * The tail of the screen, in one line, for the log.
     *
     * An agent that does not leave on command explains itself only by what was
     * on its screen: a menu it was waiting on, a question it asked, a command it
     * did not understand. Empty lines go out and the rest is strung together —
     * this is a log line, not a picture.
     */
    private static function tail(string $instance): string
    {
        $rows = array_filter(
            preg_split('/\R/', self::capture($instance, self::LIVE_LINES)['text']) ?: [],
            static fn ($row) => trim($row) !== ''
        );

        $rows = array_map('trim', array_slice(array_values($rows), -self::TAIL_LINES));

        return Str::limit(implode(' | ', $rows), self::TAIL_CHARS);
    }

    /** Where the agent looks for its rules: its working directory. */
    public static function rulesFile(array $agent): string
    {
        return ($agent['cwd'] !== '' ? $agent['cwd'] : base_path()) . '/' . self::RULES;
    }

    public static function hasRules(array $agent): bool
    {
        return is_file(self::rulesFile($agent));
    }

    public static function pollFile(string $instance): string
    {
        return storage_path(self::POLL_DIR . '/' . $instance . '.log');
    }

    /**
     * The pane as text, with `$lines` of what scrolled above it. `0` — all of it.
     *
     * The screen alone is not enough to read by: an answer longer than fifty
     * lines leaves it upwards while it is still being written. So even the poll
     * takes the screen together with a piece of the history.
     *
     * `-J` puts a line that did not fit the width back together: it is one line
     * of the agent, broken by the terminal, and on a page it has no reason to
     * stay broken.
     *
     * @return array{text: string, error: string}
     */
    private static function capture(string $instance, int $lines): array
    {
        $run = Process::run(sprintf(
            'tmux capture-pane -p -J -S %s -t %s',
            $lines > 0 ? '-' . $lines : '-',
            escapeshellarg($instance),
        ));

        if (! $run->successful()) {
            return ['text' => '', 'error' => trim($run->errorOutput())];
        }

        // the pane is fifty rows tall and mostly empty: what is below the last
        // line of text is not the silence of the agent, it is the pane
        return ['text' => rtrim($run->output(), " \n"), 'error' => ''];
    }

    /**
     * The environment of the settings as arguments of new-session.
     *
     * `-e` of tmux, not a prefix of the command: the variable belongs to the
     * session, so it is there for whatever the agent starts later, and it does
     * not have to be read as a shell line first.
     *
     * php-fpm hands the agent an environment of its own, and it is nearly
     * empty: `HOME` of `www-data`, `PATH` of the service. What the agent needs
     * instead of that is written in the settings, not here.
     */
    private static function env(array $env): string
    {
        $line = '';

        foreach ($env as $name => $value) {
            $line .= ' -e ' . escapeshellarg($name . '=' . $value);
        }

        return $line;
    }

    /**
     * The session is over: tmux lets it go, the poll file goes with it.
     *
     * The killing is unconditional. A session with a dead pane is still a
     * session for tmux — remain-on-exit keeps it standing for the sake of its
     * last screen, and once that screen has been read there is nothing to keep.
     */
    private static function finish(string $instance): void
    {
        Process::run('tmux kill-session -t ' . escapeshellarg($instance));

        File::delete(self::pollFile($instance));
    }

    /** Marks that the browser is still watching. */
    private static function poll(string $instance): void
    {
        File::ensureDirectoryExists(storage_path(self::POLL_DIR));

        File::put(self::pollFile($instance), (string) time());
    }

    /**
     * The name of the session as it may be.
     *
     * It is built here and comes back from the browser, and on the way back it
     * turns into an argument of tmux and a name of a file. Letters, digits and
     * a dash, nothing else: not a dot, not a slash, not a space.
     */
    private static function checkName(string $instance): void
    {
        if (preg_match('/^[A-Za-z0-9-]{1,64}$/', $instance) !== 1) {
            throw new \Exception('bad session name: ' . $instance);
        }
    }
}
