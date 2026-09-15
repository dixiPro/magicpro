<?php

namespace MagicProSrc\Console\Aws;

/**
 * Shows how AWS mail is set up right now. Reads and changes nothing.
 *
 * Every section stands on its own: a key without the rights to read SNS still
 * shows what SES says. That is why there is no single try/catch around the
 * whole run — one refusal must not hide the rest of the picture.
 *
 * The last section is about the project itself. A setup correct in AWS and an
 * .env that knows nothing about it look exactly the same from outside:
 * mail goes out, events never arrive.
 */
class StatusCommand extends AwsCommand
{
    /** Some section failed: the run goes on, the exit code says so at the end. */
    private bool $failed = false;

    protected $signature = 'magicpro:aws-status
        {--file=aws-setup.ini : settings of the site}';

    protected $description = 'Shows the state of AWS mail for one site';

    public function handle(): int
    {
        $settings = $this->readSettings((string) $this->option('file'), ['region', 'domain', 'user']);

        if (! $settings) {
            return self::FAILURE;
        }

        $this->region = $settings['region'];
        $names        = $this->names($settings['user']);

        if (! $this->askSetupKey()) {
            return self::FAILURE;
        }

        $this->line('');
        $this->line('Region: ' . $this->region);

        $this->section('IAM', fn () => $this->iamUser($settings['user']));
        $this->section('SES', fn () => $this->identities($settings['domain']));
        $this->section('SNS', fn () => $this->topic($names['topic']));
        $this->section('SES events', fn () => $this->events($names['config_set']));
        $this->section('MagicPro', fn () => $this->project());

        // a picture with a hole in it is not a success: a deploy script reads
        // the exit code, not the red line in the middle
        return $this->failed ? self::FAILURE : self::SUCCESS;
    }

    /** One refused section is a line, not the end of the run. */
    private function section(string $title, callable $body): void
    {
        $this->line('');
        $this->line($title);

        try {
            $body();
        } catch (\Throwable $e) {
            $this->err($this->awsMessage($e));

            $this->failed = true;
        }
    }

    /**
     * The user of the site and what he holds.
     *
     * This is the part you look at before changing keys: how many there are,
     * how old they are, and which one the project is using right now. AWS
     * allows two per user, and a place taken by a forgotten key is the reason
     * a change of keys fails.
     */
    private function iamUser(string $user): void
    {
        $iam = $this->iam();

        try {
            $found = $iam->getUser(['UserName' => $user])->get('User');
        } catch (\Aws\Iam\Exception\IamException $e) {
            if ($e->getAwsErrorCode() === 'NoSuchEntity') {
                $this->wait('user ' . $user . ' does not exist');

                return;
            }

            throw $e;
        }

        $this->ok('user ' . $user . ', created ' . $this->when($found['CreateDate'] ?? null));

        $current = trim((string) config('services.ses.key', ''));
        $keys    = $iam->listAccessKeys(['UserName' => $user])->get('AccessKeyMetadata') ?? [];

        foreach ($keys as $key) {
            $id   = (string) $key['AccessKeyId'];
            $used = $iam->getAccessKeyLastUsed(['AccessKeyId' => $id])->get('AccessKeyLastUsed');

            $this->line('       ' . $id
                . '  ' . str_pad((string) ($key['Status'] ?? ''), 8)
                . '  created ' . $this->when($key['CreateDate'] ?? null)
                . '  used ' . $this->when($used['LastUsedDate'] ?? null)
                . ($id === $current && $current !== '' ? '   <- the project sends with this one' : ''));
        }

        count($keys) < 2
            ? $this->ok(count($keys) . ' of 2 access keys, a place for a new one')
            : $this->wait('2 of 2 access keys: a new one cannot be issued until one is deleted');

        foreach ($iam->listUserPolicies(['UserName' => $user])->get('PolicyNames') ?? [] as $name) {
            $this->line('       policy ' . $name . ' (inline)');
        }

        foreach ($iam->listAttachedUserPolicies(['UserName' => $user])->get('AttachedPolicies') ?? [] as $policy) {
            $this->line('       policy ' . ($policy['PolicyName'] ?? '') . ' (attached)');
        }
    }

    /** A date of AWS as a day, whatever type the sdk handed over. */
    private function when(mixed $date): string
    {
        if ($date === null) {
            return 'never';
        }

        try {
            return (new \DateTimeImmutable((string) $date))->format('Y-m-d');
        } catch (\Throwable) {
            return 'unknown';
        }
    }

    private function identities(string $domain): void
    {
        $ses = $this->ses();

        $identity = $ses->getEmailIdentity(['EmailIdentity' => $domain]);
        $dkim     = (string) (($identity->get('DkimAttributes') ?? [])['Status'] ?? 'NOT_STARTED');

        $identity->get('VerifiedForSendingStatus')
            ? $this->ok('domain ' . $domain . ' verified')
            : $this->wait('domain ' . $domain . ' not verified');

        $dkim === 'SUCCESS'
            ? $this->ok('DKIM ' . $dkim)
            : $this->wait('DKIM ' . $dkim);

        $account = $ses->getAccount();
        $quota   = $account->get('SendQuota') ?? [];

        $account->get('ProductionAccessEnabled')
            ? $this->ok('production access')
            : $this->wait('sandbox: mail goes to verified addresses only');

        $this->line('       quota ' . (int) ($quota['Max24HourSend'] ?? 0) . '/day, '
            . (float) ($quota['MaxSendRate'] ?? 0) . '/sec, sent '
            . (int) ($quota['SentLast24Hours'] ?? 0));

        // the list comes in pages; the first one alone looked like the whole
        // list on an account with many identities
        $token = null;

        do {
            $answer = $ses->listEmailIdentities($token ? ['NextToken' => $token] : []);

            foreach ($answer->get('EmailIdentities') ?? [] as $row) {
                $this->line('       ' . str_pad((string) ($row['IdentityType'] ?? ''), 14)
                    . ($row['IdentityName'] ?? '')
                    . (($row['SendingEnabled'] ?? false) ? '' : '  (sending off)'));
            }

            $token = $answer->get('NextToken');
        } while ($token);
    }

    private function topic(string $name): void
    {
        $sns   = $this->sns();
        $arn   = '';
        $token = null;

        do {
            $answer = $sns->listTopics($token ? ['NextToken' => $token] : []);

            foreach ($answer->get('Topics') ?? [] as $topic) {
                $candidate = (string) ($topic['TopicArn'] ?? '');

                if (str_ends_with($candidate, ':' . $name)) {
                    $arn = $candidate;
                }
            }

            $token = $answer->get('NextToken');
        } while ($token && $arn === '');

        if ($arn === '') {
            $this->wait('topic ' . $name . ' not found: aws-setup has not run');

            return;
        }

        $this->ok('topic ' . $name);

        foreach ((new Subscriptions($sns))->all($arn) as $row) {
            $row['status'] === Subscriptions::CONFIRMED
                ? $this->ok($row['endpoint'])
                : $this->wait($row['endpoint'] . ' ' . $row['status']);
        }
    }

    private function events(string $set): void
    {
        $ses = $this->ses();

        try {
            $destinations = $ses->getConfigurationSetEventDestinations(['ConfigurationSetName' => $set])
                ->get('EventDestinations') ?? [];
        } catch (\Aws\SesV2\Exception\SesV2Exception $e) {
            if ($e->getAwsErrorCode() !== 'NotFoundException') {
                throw $e;
            }

            // Not an error but a state, the same one the topic reports above:
            // aws-setup has not run yet.
            $this->wait('configuration set ' . $set . ' does not exist: aws-setup has not run');

            return;
        }

        if (! $destinations) {
            $this->wait('configuration set ' . $set . ' has no event destination');

            return;
        }

        foreach ($destinations as $row) {
            $topic  = (string) (($row['SnsDestination'] ?? [])['TopicArn'] ?? '');
            $events = $row['MatchingEventTypes'] ?? [];

            ($row['Enabled'] ?? false)
                ? $this->ok($set . ' -> ' . ($row['Name'] ?? ''))
                : $this->wait($set . ' -> ' . ($row['Name'] ?? '') . ' disabled');

            $this->line('       ' . $topic);
            $this->line('       ' . implode(' ', $events));
        }
    }

    /** What the site itself reads. Written in AWS is only half the answer. */
    private function project(): void
    {
        // config(), а не env(): показывается то, что сайт реально читает,
        // в том числе после config:cache
        $set = (string) config('magicpro_mail.configuration_set', '');

        config('magicpro_mail.ses_api')
            ? $this->ok('AWS_SesV2Client=true, mail goes through the SES API')
            : $this->line('       AWS_SesV2Client is off, mail goes through SMTP');

        $set === ''
            ? $this->wait('AWS_SES_CONFIGURATION_SET is empty: SES sends no events at all')
            : $this->ok('AWS_SES_CONFIGURATION_SET=' . $set);

        $this->line('       MAIL_HOST=' . config('mail.mailers.smtp.host'));
        $this->line('       ses region=' . config('services.ses.region'));
    }
}
