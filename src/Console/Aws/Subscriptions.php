<?php

namespace MagicProSrc\Console\Aws;

use Aws\Sns\SnsClient;

/**
 * Subscriptions of one SNS topic.
 *
 * Both commands work with them — setup while making the first one, webhook
 * every time the address changes — so the reading, the waiting and the wording
 * of a status live in one place.
 *
 * Confirming is not here and cannot be: SNS sends SubscriptionConfirmation to
 * the site, and AwsHookHandler answers it. All this class does is wait and look
 * again.
 */
class Subscriptions
{
    public const CONFIRMED = 'CONFIRMED';
    public const PENDING   = 'PENDING';

    /** How long a confirmation is waited for, seconds. */
    private const WAIT = 30;

    /** SNS says exactly this instead of an ARN while a subscription is pending. */
    private const NOT_CONFIRMED = 'PendingConfirmation';

    public function __construct(private SnsClient $sns) {}

    /**
     * @return array<int, array{endpoint: string, protocol: string, arn: string, status: string}>
     */
    public function all(string $topicArn): array
    {
        $rows  = [];
        $token = null;

        do {
            $params = ['TopicArn' => $topicArn];

            if ($token) {
                $params['NextToken'] = $token;
            }

            $answer = $this->sns->listSubscriptionsByTopic($params);

            foreach ($answer->get('Subscriptions') ?? [] as $row) {
                $arn = (string) ($row['SubscriptionArn'] ?? '');

                $rows[] = [
                    'endpoint' => (string) ($row['Endpoint'] ?? ''),
                    'protocol' => (string) ($row['Protocol'] ?? ''),
                    'arn'      => $arn,
                    'status'   => $arn === self::NOT_CONFIRMED ? self::PENDING : self::CONFIRMED,
                ];
            }

            $token = $answer->get('NextToken');
        } while ($token);

        return $rows;
    }

    /**
     * Subscribes the endpoint unless it is already there, then waits for the
     * confirmation to come back through the site.
     */
    public function ensure(string $topicArn, string $endpoint): string
    {
        $found = $this->find($topicArn, $endpoint);

        if (! $found) {
            $this->sns->subscribe([
                'TopicArn' => $topicArn,
                'Protocol' => 'https',
                'Endpoint' => $endpoint,
            ]);
        } elseif ($found['status'] === self::CONFIRMED) {
            return self::CONFIRMED;
        }

        $waited = 0;

        while ($waited < self::WAIT) {
            sleep(1);
            $waited++;

            $row = $this->find($topicArn, $endpoint);

            if ($row && $row['status'] === self::CONFIRMED) {
                return self::CONFIRMED;
            }
        }

        return self::PENDING;
    }

    /**
     * Removes every other https subscription: a topic serves one site, and an
     * address left from a move keeps receiving events into nowhere.
     *
     * @return array<int, string> endpoints that were dropped
     */
    public function dropOthers(string $topicArn, string $keep): array
    {
        $dropped = [];

        foreach ($this->all($topicArn) as $row) {
            if ($row['protocol'] !== 'https' || $row['endpoint'] === $keep) {
                continue;
            }

            // A pending subscription has no ARN to unsubscribe by. It dies on
            // its own after three days, so it is only reported.
            if ($row['status'] === self::PENDING) {
                continue;
            }

            $this->sns->unsubscribe(['SubscriptionArn' => $row['arn']]);

            $dropped[] = $row['endpoint'];
        }

        return $dropped;
    }

    /** @return array{endpoint: string, protocol: string, arn: string, status: string}|null */
    public function find(string $topicArn, string $endpoint): ?array
    {
        foreach ($this->all($topicArn) as $row) {
            if ($row['endpoint'] === $endpoint && $row['protocol'] === 'https') {
                return $row;
            }
        }

        return null;
    }
}
