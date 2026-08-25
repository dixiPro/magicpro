<?php

namespace MagicProSrc\Console\Aws;

/**
 * Makes the site hear back about its mail: topic, subscription, event set.
 *
 * Everything here exists for one purpose — to tell the site what happened to a
 * letter. A site that does not need to know sends mail perfectly well without
 * any of it, which is why none of this lives in aws-setup.
 *
 * The command builds what is missing and brings what is there to the required
 * shape. Changing the address is the same run: the new one is subscribed, the
 * old ones are dropped. That is the case that repeats — a domain is set up
 * once, an address changes with every move and every test stand.
 */
class WebhookCommand extends AwsCommand
{
    protected $signature = 'magicpro:aws-webhook
        {--file=aws-webhook.ini : file holding the webhook address}
        {--setup=aws-setup.ini : settings of the site, for the region and the user}';

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
        $webhook = $this->readSettings((string) $this->option('file'), ['webhook']);

        if (! $webhook) {
            return self::FAILURE;
        }

        $settings = $this->readSettings((string) $this->option('setup'), ['region', 'user']);

        if (! $settings) {
            return self::FAILURE;
        }

        $endpoint = $webhook['webhook'];

        if (! str_starts_with($endpoint, 'https://')) {
            $this->err('the webhook address must be https: ' . $endpoint);

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
