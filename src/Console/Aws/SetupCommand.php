<?php

namespace MagicProSrc\Console\Aws;

use Illuminate\Support\Facades\Http;
use MagicProSrc\Mail\AwsHookHandler;

/**
 * Sets up AWS for one site: the mail it sends, and the events it hears back.
 *
 * One run per site, one command for the whole job. The two halves used to be
 * two commands, and the split cost more than it saved: the settings file is
 * one, the setup key is typed once, and nobody remembers a second command half
 * a year later.
 *
 * The first half — IAM user, rights, access key, SMTP password — is what makes
 * mail leave the site. The second — topic, its policy, subscription, set of
 * configuration — is what makes the site hear what happened to a letter.
 *
 * The domain and the sending address are verified before all this, by hand
 * through DNS: there is nothing to automate about a CNAME.
 *
 * Everything the command touches, it brings to shape whatever was there
 * before. The one thing it asks about is the access key, and there the command
 * only adds: a new key is issued beside the old one, which keeps working until
 * the new secret is in the project. Nothing is deactivated and nothing is
 * deleted here — that is `magicpro:aws-remove`. Answering no is the way to run
 * the command for the events alone.
 */
class SetupCommand extends AwsCommand
{
    protected $signature = 'magicpro:aws-setup
        {--file=aws-setup.ini : settings of the site}
        {--out= : where to write the result, by default storage/app/private/magic/aws/aws-domain-date.result}';

    protected $description = 'Sets a site up in AWS: keys to send mail with, events back';

    /**
     * Events the configuration set sends to the topic: the four the hook acts
     * on. There used to be eight — send, click, reject and rendering failure
     * came too, were taken and thrown away, and cost an SNS message each.
     */
    private const EVENTS = [
        'DELIVERY',
        'BOUNCE',
        'COMPLAINT',
        'OPEN',
    ];

    /** Where the result goes when --out says nothing: not served, not in git. */
    private const RESULT_DIR = 'app/private/magic/aws';

    /** Ports the SES SMTP interface answers on. 465 is ssl, the rest are tls. */
    private const SMTP_PORTS = ['25', '465', '587', '2465', '2587'];

    /** How many access keys AWS allows one user, inactive ones counted. */
    private const KEY_LIMIT = 2;

    /** Where the result went: chosen once, written into more than once. */
    private string $resultFile = '';

    /** The first writing failed: nothing more is written anywhere, see writeResult(). */
    private bool $resultFailed = false;

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
        $smtpPort     = $this->smtpPort($settings['smtp_port'] ?? '587');

        if ($smtpPort === '' || ! $this->checkUser($user)) {
            return self::FAILURE;
        }
        $names        = $this->names($user);
        $webhook      = $this->webhook($settings, $domain);

        if (! $this->confirmSettings($settings, $names, $webhook)) {
            $this->line('Nothing changed.');

            return self::FAILURE;
        }

        if (! $this->askSetupKey()) {
            return self::FAILURE;
        }

        $this->line('');

        $failed = false;

        try {
            if (! $this->checkDomain($domain)) {
                return self::FAILURE;
            }

            $this->user($user, $domain);
            $this->policy($user, $names['policy']);

            if ($this->confirmNewKey()) {
                $failed = ! $this->accessKey($user);

                $this->smtp($smtpPort);

                // A guess, not an answer: the command knows the domain and
                // nothing about the mailbox the site should write from. The
                // note beside it says so, so that a blind copy does not replace
                // a working address with `info@`.
                $this->env['MAIL_FROM_ADDRESS'] = 'info@' . $domain;
                $this->env['MAIL_FROM_NAME']    = '"' . $user . '"';

                $this->notes[] = 'MAIL_FROM_ADDRESS and MAIL_FROM_NAME above are a guess by the domain:';
                $this->notes[] = 'put the address the site really writes from, and a name for people.';

                // The secret is written down the moment it exists. Everything
                // after this — the topic, the subscription, the wait for the
                // webhook — takes time and can be interrupted, and AWS shows a
                // secret once: an interrupted run would leave a key nobody can
                // use and a place in the user taken by it.
                if (! $failed && ! $this->writeResult($domain)) {
                    $failed = true;
                }
            } else {
                $this->wait('the access key is left as it is');
            }

            $this->events($names, $user, $webhook);
        } catch (\Throwable $e) {
            $this->err($this->awsMessage($e));

            $failed = true;
        }

        // The result is written even after a failure: the secret is handed out
        // once, and an issued key must not disappear with the error message.
        // No key issued — no secret, so there is nothing to write and the one
        // line that matters goes on the screen.
        if (! $this->env) {
            $this->showNotes();
        } elseif (! $this->writeResult($domain)) {
            $failed = true;
        }

        // A half-done setup is not a success. The message alone is not enough:
        // a run inside a deploy script is read by its exit code, and zero after
        // a broken IAM or a subscription that never confirmed would let the
        // deployment go on with mail that does not work.
        if ($failed) {
            $this->err('the setup did not finish: read the messages above, fix the cause and run it again.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * The address SNS will knock at.
     *
     * A path is what the settings file holds — the domain is already written
     * above it, and repeating it is one more place to mistype. The scheme is
     * not a choice either: SNS gets an https endpoint or nothing.
     *
     * MagicPro has one hook and its path never changes, so the line may be
     * left out altogether. A whole address is still taken as it is: a site can
     * live on one domain and send from another.
     */
    private function webhook(array $settings, string $domain): string
    {
        $webhook = (string) ($settings['webhook'] ?? '');

        if ($webhook === '') {
            $webhook = '/awsHook';
        }

        return str_starts_with($webhook, '/')
            ? 'https://' . $domain . $webhook
            : $webhook;
    }

    /**
     * Everything the run will use, on one screen, before anything is created.
     *
     * The file is typed by hand and read half a year later. A stale region or
     * a user renamed since the last time is caught here — otherwise it is
     * caught by a pile of resources under the wrong name in the console.
     */
    private function confirmSettings(array $settings, array $names, string $webhook): bool
    {
        $this->line('');
        $this->line('Settings of the site:');

        foreach ($settings as $key => $value) {
            $this->line('  ' . str_pad((string) $key, 18)
                . (is_array($value) ? implode(' ', $value) : $value));
        }

        $this->line('');
        $this->line('Will be created in AWS or brought to this shape:');
        $this->line('  IAM user          ' . $settings['user']);
        $this->line('  policy            ' . $names['policy']);
        $this->line('  SNS topic         ' . $names['topic']);
        $this->line('  configuration set ' . $names['config_set']);
        $this->line('  event destination ' . $names['event_destination']);
        $this->line('  webhook           ' . $webhook);
        $this->line('');

        return $this->confirm('Continue?', false);
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
     *
     * They are a mark of the creation — who made the user, for which domain,
     * when — and are written once. A user that exists keeps its tags: a later
     * run for another domain does not rewrite the history of the first one.
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

    /**
     * Inline, two actions: the user exists for one job.
     *
     * The policy with this name belongs to MagicPro and is written whole on
     * every run, whatever was in it. The same holds for the topic policy
     * below. Hand edits of either are not kept — that is the price of a
     * command that brings everything to one shape.
     */
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
        $this->line('A new Access Key will be issued. Earlier keys of the user are left as they are:');
        $this->line('the old one keeps working until you put the new one into the project and delete it.');
        $this->line('No — no key is touched and only the events are set up.');

        return $this->confirm('Issue a new key?', false);
    }

    /**
     * A new key for the user. The secret goes straight into the result: it is
     * handed out once.
     *
     * Nothing else is touched. The old key is not deactivated and not deleted,
     * because that is the one thing this command must not decide: the site
     * keeps sending mail with it until somebody puts the new secret into the
     * project and checks that mail still goes. Deleting comes after that, by
     * hand or by `magicpro:aws-remove`.
     *
     * AWS allows two access keys per user, counting inactive ones. Two is
     * exactly enough for a change of keys and not enough for a third: with both
     * places taken the command says so and touches nothing — deactivating
     * somebody's working key to free a place is not its business.
     *
     * False means a key was asked for and not issued: the run is not a success,
     * whatever else went well.
     */
    private function accessKey(string $user): bool
    {
        $iam  = $this->iam();
        $keys = $iam->listAccessKeys(['UserName' => $user])->get('AccessKeyMetadata') ?? [];

        if (count($keys) >= self::KEY_LIMIT) {
            $this->err('user ' . $user . ' already has ' . self::KEY_LIMIT . ' access keys, AWS allows no more.');

            foreach ($keys as $key) {
                $this->line('    ' . $key['AccessKeyId']
                    . '  ' . str_pad((string) ($key['Status'] ?? ''), 8)
                    . '  created ' . $this->when($key['CreateDate'] ?? null));
            }

            $this->line('Delete one of them — magicpro:aws-remove --key — and run this again.');

            return false;
        }

        $created = $iam->createAccessKey(['UserName' => $user])->get('AccessKey');

        $this->ok('access key ' . $created['AccessKeyId'] . ' created');

        foreach ($keys as $key) {
            $this->wait('old key ' . $key['AccessKeyId'] . ' still works, delete it when the new one is in place');
        }

        $this->env['AWS_SesV2Client']       = 'true';
        $this->env['AWS_ACCESS_KEY_ID']     = $created['AccessKeyId'];
        $this->env['AWS_SECRET_ACCESS_KEY'] = $created['SecretAccessKey'];
        // The key itself works in every region; the region here says where SES
        // and its events live, and it travels to the project with the key.
        $this->env['AWS_DEFAULT_REGION']    = $this->region;

        $this->notes[] = 'Access key:    ' . $created['AccessKeyId'];

        return true;
    }

    /** A date of AWS as a day, whatever type the sdk handed over. */
    private function when(mixed $date): string
    {
        if ($date === null) {
            return 'unknown';
        }

        try {
            return (new \DateTimeImmutable((string) $date))->format('Y-m-d');
        } catch (\Throwable) {
            return 'unknown';
        }
    }

    /**
     * The SMTP password is the secret run through the signing chain of SigV4.
     * Counted here, locally: AWS is not asked and has nothing to answer.
     */
    /**
     * The port of the SES SMTP interface, checked before anything is done.
     *
     * SES answers on four of them and nowhere else. An unknown number used to
     * travel through the whole setup into the result file, and the site then
     * failed on the first letter, far away from the cause.
     *
     * An empty answer means the setup must not start.
     */
    private function smtpPort(string $port): string
    {
        $port = trim($port);

        if (in_array($port, self::SMTP_PORTS, true)) {
            return $port;
        }

        $this->err('smtp_port = ' . $port . ': SES answers on ' . implode(', ', self::SMTP_PORTS) . '.');
        $this->line('587 is the usual one; 465 is the same over ssl.');

        return '';
    }

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
     * The half that makes the site hear back: topic, its policy, the set of
     * configuration with the events, and the subscription of the address.
     *
     * The first three need nothing from the site and are built always. The
     * subscription needs the site to be up and answering, which on a fresh
     * domain it usually is not — so a silent address is a warning, not the end
     * of the run: everything else is already in place, and the subscription is
     * one more run away.
     */
    private function events(array $names, string $user, string $webhook): void
    {
        $this->line('');

        $arn = $this->topic($names['topic']);

        $this->topicPolicy($arn, $user);
        $this->configurationSet($names['config_set'], $names['event_destination'], $arn);

        $status = $this->subscription($arn, $webhook);

        // The line is written commented on purpose. It is what turns the events
        // on, and until the address really answers there is nothing to turn on:
        // uncommenting is the last step, done by hand, when the site is up.
        $this->notes[] = 'webhook:       ' . $webhook . '  ' . $status;
        $this->notes[] = '';
        $this->notes[] = 'Set the webhook up at the address above, then uncomment the line below:';
        $this->notes[] = 'AWS_SES_CONFIGURATION_SET=' . $names['config_set'];
        $this->notes[] = '';
        $this->notes[] = 'And this one, so the hook takes events of this topic alone:';
        $this->notes[] = 'AWS_SNS_TOPIC_ARN=' . $arn;
    }

    /** CreateTopic answers with the existing one when the name is taken. */
    private function topic(string $name): string
    {
        $arn = (string) $this->sns()->createTopic(['Name' => $name])->get('TopicArn');

        $this->ok('SNS topic ' . $name);

        return $arn;
    }

    /**
     * Without this SES cannot publish into the topic, and the setup ends with a
     * webhook that stays silent for no visible reason.
     *
     * The owner statement goes in too: SetTopicAttributes replaces the policy
     * whole, and a topic nobody but SES may touch is a topic we cannot manage.
     */
    private function topicPolicy(string $arn, string $user): void
    {
        $account = $this->accountFromArn($arn);

        $policy = [
            'Version'   => '2012-10-17',
            // Named after the user like everything else: a name in the console
            // must say whose it is.
            'Id'        => $user . '-topic-policy',
            'Statement' => [
                [
                    'Sid'       => 'owner',
                    'Effect'    => 'Allow',
                    'Principal' => ['AWS' => '*'],
                    'Action'    => [
                        'SNS:GetTopicAttributes',
                        'SNS:SetTopicAttributes',
                        'SNS:AddPermission',
                        'SNS:RemovePermission',
                        'SNS:DeleteTopic',
                        'SNS:Subscribe',
                        'SNS:ListSubscriptionsByTopic',
                        'SNS:Publish',
                    ],
                    'Resource'  => $arn,
                    'Condition' => ['StringEquals' => ['AWS:SourceOwner' => $account]],
                ],
                [
                    'Sid'       => 'ses-publish',
                    'Effect'    => 'Allow',
                    'Principal' => ['Service' => 'ses.amazonaws.com'],
                    'Action'    => 'SNS:Publish',
                    'Resource'  => $arn,
                    'Condition' => ['StringEquals' => ['AWS:SourceAccount' => $account]],
                ],
            ],
        ];

        $this->sns()->setTopicAttributes([
            'TopicArn'       => $arn,
            'AttributeName'  => 'Policy',
            'AttributeValue' => json_encode($policy),
        ]);

        $this->ok('topic policy: ses.amazonaws.com may publish');
    }

    /** The set is brought to the required shape whatever was there before. */
    private function configurationSet(string $set, string $destination, string $topicArn): void
    {
        $ses = $this->ses();

        try {
            $ses->getConfigurationSet(['ConfigurationSetName' => $set]);
            $this->ok('configuration set ' . $set . ' exists');
        } catch (\Aws\SesV2\Exception\SesV2Exception $e) {
            if ($e->getAwsErrorCode() !== 'NotFoundException') {
                throw $e;
            }

            $ses->createConfigurationSet(['ConfigurationSetName' => $set]);
            $this->ok('configuration set ' . $set . ' created');
        }

        $event = [
            'Enabled'            => true,
            'MatchingEventTypes' => self::EVENTS,
            'SnsDestination'     => ['TopicArn' => $topicArn],
        ];

        $existing = $ses->getConfigurationSetEventDestinations(['ConfigurationSetName' => $set])
            ->get('EventDestinations') ?? [];

        $found = false;

        foreach ($existing as $row) {
            if (($row['Name'] ?? '') === $destination) {
                $found = true;
            }
        }

        if ($found) {
            $ses->updateConfigurationSetEventDestination([
                'ConfigurationSetName' => $set,
                'EventDestinationName' => $destination,
                'EventDestination'     => $event,
            ]);

            $this->ok('event destination ' . $destination . ' updated');
        } else {
            $ses->createConfigurationSetEventDestination([
                'ConfigurationSetName' => $set,
                'EventDestinationName' => $destination,
                'EventDestination'     => $event,
            ]);

            $this->ok('event destination ' . $destination . ' created');
        }
    }

    /** The address is subscribed only if it answers. @return string status for the result file */
    private function subscription(string $arn, string $endpoint): string
    {
        if (! str_starts_with($endpoint, 'https://')) {
            $this->err('the webhook address must be https: ' . $endpoint);

            return 'NOT SUBSCRIBED';
        }

        if (! $this->reachable($endpoint)) {
            $this->line('Not subscribed. Run the command again when the address answers.');

            return 'NOT SUBSCRIBED';
        }

        $subscriptions = new Subscriptions($this->sns());

        $this->show($subscriptions->all($arn));

        $status = $subscriptions->ensure($arn, $endpoint);

        if ($status === Subscriptions::CONFIRMED) {
            $this->ok($endpoint . ' CONFIRMED');
        } else {
            $this->wait($endpoint . ' PENDING: is the site reachable from outside?');
        }

        foreach ($subscriptions->dropOthers($arn, $endpoint) as $dropped) {
            $this->ok('unsubscribed ' . $dropped);
        }

        return $status;
    }

    /**
     * We knock at the address before it is handed to SNS.
     *
     * SNS confirms a subscription by knocking too, and a knock that lands
     * nowhere leaves a PENDING subscription hanging for 48 hours — while the
     * command reports success on everything else. Cheaper to find out now.
     *
     * The knock is a POST with a Type of our own: the handler answers a Type it
     * does not know with the same `{"status": true}`, and the dynamic router of
     * the site — the usual reason the address is silent — answers with a page.
     * So the answer says not only «alive» but «it is our hook that is alive».
     */
    private function reachable(string $endpoint): bool
    {
        try {
            $answer = Http::timeout(10)->withoutRedirecting()->post($endpoint, ['Type' => AwsHookHandler::PING]);
        } catch (\Throwable $e) {
            $this->err('the address does not answer: ' . $e->getMessage());

            return false;
        }

        if ($answer->redirect()) {
            $this->err('the address redirects to ' . $answer->header('Location'));

            return false;
        }

        if (! $answer->successful()) {
            $this->err('the address answers ' . $answer->status() . ': ' . $endpoint);

            return false;
        }

        if ($answer->json('status') !== true) {
            $this->err('answered, but not by the MagicPro hook: ' . $endpoint);
            $this->line('POST /awsHook has to reach AwsHookHandler: check that the dynamic router lets it through.');

            return false;
        }

        $this->ok('webhook ' . $endpoint . ' answers');

        return true;
    }

    /** @param array<int, array{endpoint: string, protocol: string, status: string}> $rows */
    private function show(array $rows): void
    {
        if (! $rows) {
            return;
        }

        $this->line('Subscriptions of the topic:');

        foreach ($rows as $row) {
            $this->line('  ' . $row['protocol'] . '  ' . $row['status'] . '  ' . $row['endpoint']);
        }
    }

    /**
     * The only place where the secrets of the project are written down. Never
     * the screen, never a log.
     *
     * By default the file goes to `storage/app/private/magic/aws/`: the web
     * does not serve it and git does not see it. The command used to write it
     * into the project root and add `*.result` to `.gitignore` on its own —
     * one more file of the site changed by a command about AWS.
     *
     * The file is born with the rights of its owner alone, 0600, and never
     * lands on a file that was there before: a secret written over somebody's
     * file, or readable for a moment by anybody, is worse than a refusal.
     * The second writing of the same run goes through a temporary file and
     * rename(), so the file is whole at every moment.
     *
     * False means the file is not written. The key is then useless — AWS shows
     * its secret once — and the message says what to do with it.
     */
    private function writeResult(string $domain): bool
    {
        // once refused, always refused: a second try at the end of the run
        // would go through replaceSecretFile() and land on the very file the
        // first one declined to overwrite
        if ($this->resultFailed) {
            return false;
        }

        $text = '# magicpro:aws-setup, ' . $domain . ', ' . date('Y-m-d H:i') . PHP_EOL . PHP_EOL;

        foreach ($this->env as $key => $value) {
            $text .= $key . '=' . $value . PHP_EOL;
        }

        $text .= PHP_EOL;

        foreach ($this->notes as $note) {
            $text .= ($note === '' ? '' : '# ' . $note) . PHP_EOL;
        }

        $first = $this->resultFile === '';

        // The name is chosen once: the file is written the moment the secret
        // appears and again at the end, and the second writing lands on the
        // same file instead of leaving two.
        if ($first) {
            $path = (string) $this->option('out');

            $this->resultFile = $path !== ''
                ? $this->resolvePath($path)
                : storage_path(self::RESULT_DIR . '/aws-' . $domain . '-' . date('Y-m-d_His') . '.result');
        }

        $file  = $this->resultFile;
        $error = $first ? $this->createSecretFile($file, $text) : $this->replaceSecretFile($file, $text);

        if ($error !== '') {
            $this->resultFailed = true;

            $this->err('the result is not written: ' . $error);

            if (isset($this->env['AWS_ACCESS_KEY_ID'])) {
                $this->line('The new key is useless without its secret: delete it —');
                $this->line('magicpro:aws-remove --key=' . $this->env['AWS_ACCESS_KEY_ID'] . ' — and run the setup again.');
            }

            return false;
        }

        if ($first) {
            $this->line('');
            $this->ok('result: ' . $file);
            $this->line('Copy the upper block into .env, then delete the file.');
        }

        return true;
    }

    /** A new file, 0600 from birth. Empty string — written; otherwise the reason. */
    private function createSecretFile(string $file, string $text): string
    {
        $dir = dirname($file);

        if (! is_dir($dir) && ! @mkdir($dir, 0700, true) && ! is_dir($dir)) {
            return 'cannot create the folder ' . $dir;
        }

        // umask decides the rights of a new file; 0077 leaves the owner alone,
        // so there is no moment when the file is readable for anybody else
        $umask  = umask(0077);
        $handle = @fopen($file, 'x');
        umask($umask);

        if ($handle === false) {
            return is_file($file)
                ? $file . ' already exists, it is not overwritten: give another --out or move it away'
                : 'cannot create ' . $file;
        }

        $written = @fwrite($handle, $text);
        $closed  = @fclose($handle);

        if ($written !== strlen($text) || ! $closed) {
            @unlink($file);

            return 'cannot write ' . $file;
        }

        @chmod($file, 0600);

        return '';
    }

    /** The same file once more, whole at every moment: a temporary one and rename(). */
    private function replaceSecretFile(string $file, string $text): string
    {
        // tempnam() creates the file with 0600 itself
        $temp = @tempnam(dirname($file), '.aws-');

        if ($temp === false) {
            return 'cannot create a temporary file beside ' . $file;
        }

        if (@file_put_contents($temp, $text) !== strlen($text) || ! @rename($temp, $file)) {
            @unlink($temp);

            return 'cannot rewrite ' . $file;
        }

        @chmod($file, 0600);

        return '';
    }

    /** No key issued, so no file: the notes are all there is, and no secret in them. */
    private function showNotes(): void
    {
        if (! $this->notes) {
            return;
        }

        $this->line('');
        $this->line('For .env:');

        foreach ($this->notes as $note) {
            $this->line($note === '' ? '' : '  ' . $note);
        }

        $this->line('');
        $this->line('The configuration set line is the switch of the events: uncomment it when');
        $this->line('the webhook really answers, not before.');
    }
}
