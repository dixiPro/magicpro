<?php

namespace MagicProSrc\Mail;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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
    public function handle(Request $request): JsonResponse
    {
        $raw = $request->getContent();

        Log::info('awsHook received', ['body' => $raw]);

        $envelope = json_decode($raw, true) ?: [];
        $type = (string) ($envelope['Type'] ?? '');

        if ($type === 'SubscriptionConfirmation') {
            $subscribeUrl = (string) ($envelope['SubscribeURL'] ?? '');

            if ($subscribeUrl !== '') {
                Http::get($subscribeUrl);
                Log::info('awsHook subscription confirmed', ['url' => $subscribeUrl]);
            }

            return response()->json(['status' => true]);
        }

        if ($type === 'Notification') {
            $message = json_decode((string) ($envelope['Message'] ?? ''), true) ?: [];

            $this->applyEvent($message);
        }

        return response()->json(['status' => true]);
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
                $mailMessage->update(['status' => MagicProMailMessage::STATUS_DELIVERED]);
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

        // $this->writeLog();
    }

    protected function writeLog(): void
    {  // $message = json_decode(
        //     (string) ($envelope['Message'] ?? '{}'),
        //     true
        // );

        // unset($envelope['Message']);
        // Log::info(
        //     "awsHook received\n" .
        //         json_encode(
        //             [
        //                 'envelope' => $envelope,
        //                 'message' => $message,
        //             ],
        //             JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        //         )
        // );   
    }
}
