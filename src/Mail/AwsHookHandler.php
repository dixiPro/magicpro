<?php

namespace MagicProSrc\Mail;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use MagicProDatabaseModels\MagicProMailMessage;
use MagicProDatabaseModels\MagicProEmailAddress;

/**
 * Handles AWS SNS webhook calls for SES email events (delivery, open,
 * bounce, complaint). SES publishes events to an SNS topic, which delivers
 * them here as an HTTPS subscription: the SNS envelope has Type/Message,
 * where Message is the actual SES event encoded as a JSON string.
 */
class AwsHookHandler
{
    /**
     * The Type of the knock the site itself makes: the webhook command before
     * touching AWS, and the diagnostics of the admin panel on every visit.
     *
     * It is answered like any unknown Type and is the one thing that stays out
     * of the log: a line per visit to the start page would bury the events the
     * log is kept for.
     */
    public const PING = 'MagicProPing';

    /**
     * How old an envelope may be. SNS retries for hours, so the window is not
     * tight; it exists to keep a signed message of last year from being played
     * back today — the signature alone stays valid forever.
     */
    private const WINDOW = 3600;

    /** How long a MessageId is remembered, to answer a repeat with silence. */
    private const SEEN = 86400;

    public function handle(Request $request): JsonResponse
    {
        $envelope = json_decode($request->getContent(), true) ?: [];
        $type     = (string) ($envelope['Type'] ?? '');

        // our own knock: it is not signed and touches nothing, so it is answered
        // before any of the checks and stays out of the log
        if ($type === self::PING) {
            return response()->json(['status' => true]);
        }

        // the route is public — no session, no csrf, no token — so the signature
        // is the only thing that tells an event of ours from a forged one. Until
        // it is checked nothing here is trusted: neither the status of a letter,
        // nor the address to knock back at
        if (! SnsSignature::valid($envelope)) {
            Log::warning('awsHook rejected', [
                'type'      => $type,
                'messageId' => (string) ($envelope['MessageId'] ?? ''),
                'topic'     => (string) ($envelope['TopicArn'] ?? ''),
            ]);

            return response()->json(['status' => false, 'errorMsg' => 'signature'], 403);
        }

        if (! $this->fresh($envelope) || ! $this->firstTime($envelope)) {
            return response()->json(['status' => true]);
        }

        if (! $this->ourTopic($envelope)) {
            return response()->json(['status' => false, 'errorMsg' => 'topic'], 403);
        }

        // the body is not written down: it carries the whole letter event and the
        // SubscribeURL with its token. What is needed to find the event later is
        // its id and its topic
        Log::info('awsHook received', [
            'type'      => $type,
            'messageId' => (string) ($envelope['MessageId'] ?? ''),
            'topic'     => (string) ($envelope['TopicArn'] ?? ''),
        ]);

        if ($type === 'SubscriptionConfirmation') {
            $subscribeUrl = (string) ($envelope['SubscribeURL'] ?? '');

            // the signature already says the envelope is Amazon's, and this says
            // the answer goes to Amazon too: a request the server makes by an
            // address from outside is a way into the network behind it
            if ($subscribeUrl !== '' && SnsSignature::amazonUrl($subscribeUrl)) {
                $answer = Http::timeout(10)->get($subscribeUrl);

                // the answer is looked at: a 4xx or 5xx from Amazon leaves the
                // subscription pending, and a log line saying «confirmed» would
                // send whoever comes looking in the wrong direction
                $answer->successful()
                    ? Log::info('awsHook subscription confirmed', [
                        'topic' => (string) ($envelope['TopicArn'] ?? ''),
                    ])
                    : Log::warning('awsHook subscription not confirmed', [
                        'topic'  => (string) ($envelope['TopicArn'] ?? ''),
                        'status' => $answer->status(),
                    ]);
            }

            return response()->json(['status' => true]);
        }

        if ($type === 'Notification') {
            $message = json_decode((string) ($envelope['Message'] ?? ''), true) ?: [];

            $this->applyEvent($message);
        }

        return response()->json(['status' => true]);
    }

    /**
     * Not an envelope from the day before yesterday.
     *
     * The signature says who wrote the message, not when. Without a window a
     * copy caught once can be sent again at any time, and the letter it names
     * will change its status again.
     */
    private function fresh(array $envelope): bool
    {
        $stamp = strtotime((string) ($envelope['Timestamp'] ?? ''));

        if ($stamp === false) {
            Log::warning('awsHook without a timestamp', ['messageId' => (string) ($envelope['MessageId'] ?? '')]);

            return false;
        }

        if (abs(time() - $stamp) > self::WINDOW) {
            Log::warning('awsHook too old', [
                'messageId' => (string) ($envelope['MessageId'] ?? ''),
                'timestamp' => (string) $envelope['Timestamp'],
            ]);

            return false;
        }

        return true;
    }

    /**
     * The same envelope is applied once.
     *
     * SNS repeats a delivery when an answer did not arrive in time, and the
     * repeat is a legitimate one — but applying it a second time moves the
     * status of a letter again and knocks at a SubscribeURL again. The id of
     * the message is remembered for a day, and a repeat is answered with
     * success and nothing else.
     */
    private function firstTime(array $envelope): bool
    {
        $id = (string) ($envelope['MessageId'] ?? '');

        if ($id === '') {
            return true;
        }

        // add() writes only when the key is not there yet, and answers whether
        // it did: two requests at once cannot both count as the first
        return Cache::add('magic:sns:seen:' . md5($id), 1, self::SEEN);
    }

    /**
     * The topic of this site, when it is named in the settings.
     *
     * `AWS_SNS_TOPIC_ARN` empty means the check is off and any topic of Amazon
     * is accepted — that is how installations made before this setting work.
     * Named means everything else is refused: a signature of Amazon is easy to
     * get, it is enough to have a topic of one's own.
     */
    private function ourTopic(array $envelope): bool
    {
        $ours = (string) config('magicpro_mail.sns_topic_arn', '');

        if ($ours === '') {
            return true;
        }

        $topic = (string) ($envelope['TopicArn'] ?? '');

        if ($topic === $ours) {
            return true;
        }

        Log::warning('awsHook foreign topic', ['topic' => $topic]);

        return false;
    }

    protected function applyEvent(array $message): void
    {
        $eventType = (string) ($message['eventType'] ?? $message['notificationType'] ?? '');
        $providerMessageId = (string) ($message['mail']['messageId'] ?? '');

        if ($eventType === '' || $providerMessageId === '') {
            return;
        }

        $mailMessage = MagicProMailMessage::where('provider_message_id', $providerMessageId)->first();

        if (!$mailMessage) {
            Log::info('awsHook message not found', ['provider_message_id' => $providerMessageId]);
            return;
        }

        switch ($eventType) {
            case 'Delivery':
                // events arrive out of order often enough: Open after Delivery
                // is the normal case, Delivery after Open happens too. The
                // later one must not take the letter back to «delivered»
                if ($mailMessage->status !== MagicProMailMessage::STATUS_OPEN) {
                    $mailMessage->update(['status' => MagicProMailMessage::STATUS_DELIVERED]);
                }
                break;

            case 'Open':
                if ($mailMessage->updated_at && $mailMessage->updated_at->lt(now()->subSeconds(10))) {
                    $mailMessage->update(['status' => MagicProMailMessage::STATUS_OPEN]);
                }
                break;

            case 'Bounce':
            case 'Complaint':
                MagicProEmailAddress::block($mailMessage->to_email, $eventType);
                $mailMessage->update(['status' => MagicProMailMessage::STATUS_EMAILBLOCKED]);
                break;
        }
    }
}
