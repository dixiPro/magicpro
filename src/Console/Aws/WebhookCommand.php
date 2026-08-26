<?php

namespace MagicProSrc\Console\Aws;

use Illuminate\Support\Facades\Http;

/**
 * Makes the site hear back about its mail: topic, subscription, event set.
 *
 * Everything here exists for one purpose — to tell the site what happened to a
 * letter. A site that does not need to know sends mail perfectly well without
 * any of it, which is why none of this runs inside magicpro:aws-setup — though
 * both read the same settings file, one site being one file.
 *
 * The command builds what is missing and brings what is there to the required
 * shape. Changing the address is the same run: the new one is subscribed, the
 * old ones are dropped. That is the case that repeats — a domain is set up
 * once, an address changes with every move and every test stand.
 */
class WebhookCommand extends AwsCommand
{
    protected $signature = 'magicpro:aws-webhook
        {--file=aws-setup.ini : settings of the site}';

    protected $description = 'Delivers SES events of a site to a webhook address';

    /** Events the configuration set sends to the topic. */
    private const EVENTS = [
        'SEND',
        'DELIVERY',
        'BOUNCE',
        'COMPLAINT',
        'OPEN',
        'CLICK',
        'REJECT',
        'RENDERING_FAILURE',
    ];

    public function handle(): int
    {
        $settings = $this->readSettings(
            (string) $this->option('file'),
            ['region', 'user', 'webhook']
        );

        if (! $settings) {
            return self::FAILURE;
        }

        $endpoint = $settings['webhook'];

        if (! str_starts_with($endpoint, 'https://')) {
            $this->err('the webhook address must be https: ' . $endpoint);

            return self::FAILURE;
        }

        if (! $this->reachable($endpoint)) {
            return self::FAILURE;
        }

        $this->region = $settings['region'];
        $names        = $this->names($settings['user']);

        if (! $this->askSetupKey()) {
            return self::FAILURE;
        }

        $this->line('');

        try {
            $arn = $this->topic($names['topic']);

            $this->topicPolicy($arn, $settings['user']);

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

            $this->configurationSet($names['config_set'], $names['event_destination'], $arn);
        } catch (\Throwable $e) {
            $this->err($this->awsMessage($e));

            return self::FAILURE;
        }

        // No secret in this line, so it goes on the screen and needs no file of
        // its own. Without it in .env_mpro SES sends no events at all, and the
        // whole setup stays silent.
        $this->line('');
        $this->line('Add to .env_mpro:');
        $this->line('AWS_SES_CONFIGURATION_SET=' . $names['config_set']);

        return self::SUCCESS;
    }

    /**
     * We knock at the address before anything is created in AWS.
     *
     * SNS confirms a subscription by knocking too, and a knock that lands
     * nowhere leaves a PENDING subscription hanging for three days — while the
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
            $answer = Http::timeout(10)->withoutRedirecting()->post($endpoint, ['Type' => 'MagicProPing']);
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

    /** @param array<int, array{endpoint: string, protocol: string, status: string}> $rows */
    private function show(array $rows): void
    {
        if (! $rows) {
            $this->line('No subscriptions yet.');
            $this->line('');

            return;
        }

        $this->line('Subscriptions of the topic:');

        foreach ($rows as $row) {
            $this->line('  ' . $row['protocol'] . '  ' . $row['status'] . '  ' . $row['endpoint']);
        }

        $this->line('');
    }
}
