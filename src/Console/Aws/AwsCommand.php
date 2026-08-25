<?php

namespace MagicProSrc\Console\Aws;

use Aws\Exception\AwsException;
use Aws\Iam\IamClient;
use Aws\SesV2\SesV2Client;
use Aws\Sns\SnsClient;
use Illuminate\Console\Command;

/**
 * Common ground of the aws-* commands.
 *
 * Two rules hold the whole set together, and both live here.
 *
 * Parameters come from an ini file and nothing else — flags carry paths only.
 * A settings file holds no secrets, so it can lie in the open and be kept in
 * git; that is why it is the right place to look half a year later to see how
 * the account was set up.
 *
 * The setup key is asked in the terminal, every run. It is far stronger than
 * anything else the project has, and it has no business living in a file, in
 * the shell history or in an environment variable of a CI job — which is why a
 * command without a terminal refuses to work at all.
 *
 * Resource names are never stored either: they are counted from `user`, the
 * same way by every command, so two commands can never mean two different
 * topics.
 */
abstract class AwsCommand extends Command
{
    /** Credentials of the setup key, filled by askSetupKey(). */
    protected string $setupKey = '';
    protected string $setupSecret = '';

    /** Region of the current run, from the settings file. */
    protected string $region = '';

    /**
     * Reads an ini file of settings.
     *
     * @param  array<int, string>  $required  keys that must be there and not empty
     * @return array<string, string>
     */
    protected function readSettings(string $path, array $required): array
    {
        $file = $this->resolvePath($path);

        if (! is_file($file)) {
            $this->err('settings file not found: ' . $file);

            return [];
        }

        $raw = @parse_ini_file($file, false, INI_SCANNER_NORMAL);

        if ($raw === false) {
            $this->err('settings file is not a valid ini: ' . $file);

            return [];
        }

        $settings = [];

        foreach ($raw as $key => $value) {
            $settings[(string) $key] = is_array($value) ? $value : trim((string) $value);
        }

        foreach ($required as $key) {
            if (($settings[$key] ?? '') === '') {
                $this->err('settings file has no ' . $key . ': ' . $file);

                return [];
            }
        }

        $this->line('Settings: ' . $file);

        return $settings;
    }

    /** A path from a flag is taken as it is; a bare name sits in the project root. */
    protected function resolvePath(string $path): string
    {
        return str_starts_with($path, '/') ? $path : base_path($path);
    }

    /**
     * Names of every AWS resource of a site, counted from the user.
     *
     * @return array{policy: string, topic: string, config_set: string, event_destination: string}
     */
    protected function names(string $user): array
    {
        return [
            'policy'            => $user . '-policy',
            'topic'             => $user . '-events',
            'config_set'        => $user,
            'event_destination' => $user . '-sns',
        ];
    }

    /**
     * Asks for the setup key. False means there is nothing to work with.
     */
    protected function askSetupKey(): bool
    {
        // isInteractive() alone is not enough: it stays true for a piped run,
        // and the question would silently read an empty line. A key typed by
        // hand needs a real terminal, and without one the answer is no.
        $terminal = $this->input->isInteractive()
            && defined('STDIN')
            && stream_isatty(STDIN);

        if (! $terminal) {
            $this->err('the AWS setup key is typed by hand: this command needs a terminal.');

            return false;
        }

        $this->line('');
        $this->line('The setup key configures AWS. It is not the key the site sends mail with.');

        $key    = trim((string) $this->ask('AWS setup key'));
        $secret = trim((string) $this->secret('AWS setup secret'));

        if ($key === '' || $secret === '') {
            $this->err('empty key or secret.');

            return false;
        }

        $this->setupKey    = $key;
        $this->setupSecret = $secret;

        return true;
    }

    /** @return array{version: string, region: string, credentials: array{key: string, secret: string}} */
    private function clientConfig(): array
    {
        return [
            'version'     => 'latest',
            'region'      => $this->region,
            'credentials' => [
                'key'    => $this->setupKey,
                'secret' => $this->setupSecret,
            ],
        ];
    }

    protected function ses(): SesV2Client
    {
        return new SesV2Client($this->clientConfig());
    }

    protected function sns(): SnsClient
    {
        return new SnsClient($this->clientConfig());
    }

    /** IAM is global, but the client still wants a region. */
    protected function iam(): IamClient
    {
        return new IamClient($this->clientConfig());
    }

    /**
     * The account id lives inside every ARN, so nothing has to be asked of STS.
     *
     * arn:aws:sns:eu-north-1:123456789012:magicpro-events
     */
    protected function accountFromArn(string $arn): string
    {
        $parts = explode(':', $arn);

        return $parts[4] ?? '';
    }

    /** The short line of an AWS error: the SDK message carries the whole request. */
    protected function awsMessage(\Throwable $e): string
    {
        if ($e instanceof AwsException) {
            return $e->getAwsErrorCode() . ': ' . ($e->getAwsErrorMessage() ?? $e->getMessage());
        }

        return $e->getMessage();
    }

    /**
     * Adds a line to .gitignore and says so. A generated file is a file nobody
     * meant to commit, and remembering that by hand is exactly what gets
     * forgotten.
     */
    protected function gitIgnore(string $pattern): void
    {
        $file = base_path('.gitignore');

        $lines = is_file($file)
            ? preg_split('/\R/', (string) file_get_contents($file)) ?: []
            : [];

        foreach ($lines as $line) {
            if (trim($line) === $pattern) {
                return;
            }
        }

        $text = ($lines && trim(end($lines)) !== '' ? PHP_EOL : '')
            . '# magicpro aws' . PHP_EOL
            . $pattern . PHP_EOL;

        file_put_contents($file, $text, FILE_APPEND);

        $this->ok('.gitignore: added ' . $pattern);
    }

    protected function ok(string $text): void
    {
        $this->line('<info>[OK]</info>    ' . $text);
    }

    protected function wait(string $text): void
    {
        $this->line('<comment>[WAIT]</comment>  ' . $text);
    }

    protected function err(string $text): void
    {
        $this->line('<fg=red>[ERROR]</> ' . $text);
    }
}
