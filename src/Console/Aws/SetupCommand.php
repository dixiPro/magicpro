<?php

namespace MagicProSrc\Console\Aws;

/**
 * Makes a site able to send mail: IAM user, rights, access key, SMTP password.
 *
 * Run once per site. The domain and the sending address are verified before
 * that, by hand through DNS — there is nothing to automate about a CNAME.
 *
 * Events are not its business. A topic, a subscription and a configuration set
 * exist for one reason — to tell the site what happened to a letter — and that
 * is what magicpro:aws-webhook is for. A site that does not need to know sends
 * mail perfectly well without any of it.
 *
 * Everything the command does, it does always: the access key is reissued, the
 * rights are rewritten. No flag turns that off, because a half-applied setup is
 * worse than none.
 */
class SetupCommand extends AwsCommand
{
    protected $signature = 'magicpro:aws-setup
        {--file=aws-setup.ini : settings of the site}
        {--out= : where to write the result, by default aws-domain-date.result}';

    protected $description = 'Gives a site an AWS user and keys to send mail with';

    /** Lines of the result file, filled as the work goes. */
    private array $env = [];
    private array $notes = [];

    public function handle(): int
    {
        $settings = $this->readSettings(
            (string) $this->option('file'),
            ['region', 'domain', 'user']
        );

        if (! $settings) {
            return self::FAILURE;
        }

        $this->region = $settings['region'];
        $domain       = $settings['domain'];
        $user         = $settings['user'];
        $smtpPort     = $settings['smtp_port'] ?? '587';
        $names        = $this->names($user);

        if (! $this->askSetupKey()) {
            return self::FAILURE;
        }

        $this->line('');

        try {
            if (! $this->checkDomain($domain)) {
                return self::FAILURE;
            }

            $this->user($user, $domain);
            $this->policy($user, $names['policy']);

            if (! $this->confirmNewKey()) {
                $this->line('Nothing changed.');

                return self::FAILURE;
            }

            $this->accessKey($user);
            $this->smtp($smtpPort);

            $this->env['MAIL_FROM_ADDRESS'] = 'info@' . $domain;
        } catch (\Throwable $e) {
            $this->err($this->awsMessage($e));
        }

        // The result is written even after a failure: the secret is handed out
        // once, and an issued key must not disappear with the error message.
        if ($this->env) {
            $this->writeResult($domain);
        }

        return self::SUCCESS;
    }

    /** Nothing is created for a domain SES will not send from. */
    private function checkDomain(string $domain): bool
    {
        $identity = $this->ses()->getEmailIdentity(['EmailIdentity' => $domain]);

        $verified = (bool) $identity->get('VerifiedForSendingStatus');
        $dkim     = (string) (($identity->get('DkimAttributes') ?? [])['Status'] ?? 'NOT_STARTED');

        if (! $verified || $dkim !== 'SUCCESS') {
            $this->err('domain ' . $domain . ': verified=' . ($verified ? 'yes' : 'no') . ', DKIM=' . $dkim);
            $this->line('Publish the DKIM records and wait for AWS. Nothing was created.');

            return false;
        }

        $this->ok('domain ' . $domain . ' verified, DKIM SUCCESS');

        $account = $this->ses()->getAccount();

        if (! $account->get('ProductionAccessEnabled')) {
            $this->wait('the account is in the sandbox: mail goes to verified addresses only');
        }

        return true;
    }

    /**
     * The tags are the whole point of this step: a year later a pile of IAM
     * users says nothing about which site each of them serves.
     */
    private function user(string $user, string $domain): void
    {
        $iam = $this->iam();

        try {
            $iam->getUser(['UserName' => $user]);
            $this->ok('IAM user ' . $user . ' exists');
        } catch (\Aws\Iam\Exception\IamException $e) {
            if ($e->getAwsErrorCode() !== 'NoSuchEntity') {
                throw $e;
            }

            $iam->createUser([
                'UserName' => $user,
                'Tags'     => [
                    ['Key' => 'magicpro', 'Value' => '1'],
                    ['Key' => 'domain', 'Value' => $domain],
                    ['Key' => 'created', 'Value' => date('Y-m-d')],
                ],
            ]);

            $this->ok('IAM user ' . $user . ' created');
        }

        $this->notes[] = 'IAM user:      ' . $user;
    }

    /** Inline, two actions: the user exists for one job. */
    private function policy(string $user, string $policy): void
    {
        $this->iam()->putUserPolicy([
            'UserName'       => $user,
            'PolicyName'     => $policy,
            'PolicyDocument' => json_encode([
                'Version'   => '2012-10-17',
                'Statement' => [[
                    'Effect'   => 'Allow',
                    // SendEmail is what the API call needs, SendRawEmail is what
                    // the SMTP interface checks. We hand out both, so both.
                    'Action'   => ['ses:SendEmail', 'ses:SendRawEmail'],
                    'Resource' => '*',
                ]],
            ]),
        ]);

        $this->ok('policy ' . $policy);
    }

    private function confirmNewKey(): bool
    {
        $this->line('');
        $this->line('A new Access Key will be issued, every earlier key of the user is deactivated.');
        $this->line('Mail stops going out until the new key reaches .env_mpro.');

        return $this->confirm('Continue?', false);
    }

    /** The secret goes straight into the result: it is handed out once. */
    private function accessKey(string $user): void
    {
        $iam = $this->iam();

        foreach ($iam->listAccessKeys(['UserName' => $user])->get('AccessKeyMetadata') ?? [] as $key) {
            if (($key['Status'] ?? '') !== 'Active') {
                continue;
            }

            $iam->updateAccessKey([
                'UserName'    => $user,
                'AccessKeyId' => $key['AccessKeyId'],
                'Status'      => 'Inactive',
            ]);

            $this->ok('old key ' . $key['AccessKeyId'] . ' deactivated');
        }

        $created = $iam->createAccessKey(['UserName' => $user])->get('AccessKey');

        $this->ok('access key ' . $created['AccessKeyId'] . ' created');

        $this->env['AWS_SesV2Client']       = 'true';
        $this->env['AWS_ACCESS_KEY_ID']     = $created['AccessKeyId'];
        $this->env['AWS_SECRET_ACCESS_KEY'] = $created['SecretAccessKey'];
    }

    /**
     * The SMTP password is the secret run through the signing chain of SigV4.
     * Counted here, locally: AWS is not asked and has nothing to answer.
     */
    private function smtp(string $port): void
    {
        $signature = hash_hmac('sha256', '11111111', 'AWS4' . $this->env['AWS_SECRET_ACCESS_KEY'], true);
        $signature = hash_hmac('sha256', $this->region, $signature, true);
        $signature = hash_hmac('sha256', 'ses', $signature, true);
        $signature = hash_hmac('sha256', 'aws4_request', $signature, true);
        $signature = hash_hmac('sha256', 'SendRawEmail', $signature, true);

        $this->env['MAIL_MAILER']     = 'smtp';
        $this->env['MAIL_HOST']       = 'email-smtp.' . $this->region . '.amazonaws.com';
        $this->env['MAIL_PORT']       = $port;
        $this->env['MAIL_ENCRYPTION'] = $port === '465' ? 'ssl' : 'tls';
        $this->env['MAIL_USERNAME']   = $this->env['AWS_ACCESS_KEY_ID'];
        $this->env['MAIL_PASSWORD']   = base64_encode(chr(0x04) . $signature);

        $this->ok('SMTP ' . $this->env['MAIL_HOST'] . ':' . $port);
    }

    /**
     * The only place where the secrets of the project are written down. Never
     * the screen, never a log.
     */
    private function writeResult(string $domain): void
    {
        $path = (string) $this->option('out');

        if ($path === '') {
            $path = 'aws-' . $domain . '-' . date('Y-m-d_His') . '.result';
        }

        $file = $this->resolvePath($path);

        $text = '# magicpro:aws-setup, ' . $domain . ', ' . date('Y-m-d H:i') . PHP_EOL . PHP_EOL;

        foreach ($this->env as $key => $value) {
            $text .= $key . '=' . $value . PHP_EOL;
        }

        $text .= PHP_EOL;

        foreach ($this->notes as $note) {
            $text .= '# ' . $note . PHP_EOL;
        }

        file_put_contents($file, $text);
        @chmod($file, 0600);

        $this->gitIgnore('*.result');

        $this->line('');
        $this->ok('result: ' . $file);
        $this->line('Copy the upper block into .env_mpro, then delete the file.');
    }
}
