<?php

namespace MagicProSrc\Mail;

use Aws\SesV2\SesV2Client;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MagicProMailer
{
    /**
     * Создаёт и немедленно отправляет письмо.
     *
     * @param array{
     *     to: string,
     *     subject: string,
     *     html: string,
     *     from?: string,
     *     fromName?: string,
     *     replyTo?: string,
     *     mail_id?: string
     * } $params
     *
     * @return array{
     *     status: bool,
     *     mail_id: string,
     *     provider_message_id: string,
     *     raw_message: string,
     *     errorMsg: string
     * }
     */
    public static function send(array $params): array
    {
        $mailId = (string) (
            $params['mail_id']
            ?? Str::uuid()
        );

        try {
            foreach (['to', 'subject', 'html'] as $field) {
                if (
                    !isset($params[$field])
                    || trim((string) $params[$field]) === ''
                ) {
                    throw new \InvalidArgumentException(
                        "Parameter '{$field}' is required"
                    );
                }
            }

            $to = trim((string) $params['to']);

            $from = trim((string) (
                $params['from']
                ?? config('mail.from.address')
            ));

            $fromName = trim((string) (
                $params['fromName']
                ?? config('mail.from.name', '')
            ));

            $replyTo = trim((string) (
                $params['replyTo']
                ?? ''
            ));

            if ($from === '') {
                throw new \InvalidArgumentException(
                    'MAIL_FROM_ADDRESS is not configured'
                );
            }

            $email = (new Email())
                ->from(new Address($from, $fromName))
                ->to($to)
                ->subject((string) $params['subject'])
                ->html((string) $params['html']);

            if ($replyTo !== '') {
                $email->replyTo($replyTo);
            }

            $email
                ->getHeaders()
                ->addTextHeader(
                    'X-MagicPro-Mail-ID',
                    $mailId
                );

            $sentMessage = Mail::getSymfonyTransport()->send(
                $email
            );

            return [
                'status'              => true,
                'mail_id'             => $mailId,
                'provider_message_id' => $sentMessage->getMessageId(),
                'raw_message'         => $sentMessage->toString(),
                'errorMsg'            => '',
            ];
        } catch (\Throwable $e) {
            return [
                'status'              => false,
                'mail_id'             => $mailId,
                'provider_message_id' => '',
                'raw_message'         => '',
                'errorMsg'            => $e->getMessage(),
            ];
        }
    }

    /**
     * Создаёт и немедленно отправляет письмо через Amazon SES API v2.
     *
     * @param array{
     *     to: string,
     *     subject: string,
     *     html: string,
     *     from?: string,
     *     fromName?: string,
     *     replyTo?: string,
     *     mail_id?: string
     * } $params
     *
     * @return array{
     *     status: bool,
     *     mail_id: string,
     *     provider_message_id: string,
     *     raw_message: string,
     *     errorMsg: string
     * }
     */
    public static function sendByAwsApi(array $params): array
    {
        $mailId = (string) (
            $params['mail_id']
            ?? Str::uuid()
        );

        try {
            foreach (['to', 'subject', 'html'] as $field) {
                if (
                    !isset($params[$field])
                    || trim((string) $params[$field]) === ''
                ) {
                    throw new \InvalidArgumentException(
                        "Parameter '{$field}' is required"
                    );
                }
            }

            $to = trim((string) $params['to']);

            $from = trim((string) (
                $params['from']
                ?? config('mail.from.address')
            ));

            $fromName = trim((string) (
                $params['fromName']
                ?? config('mail.from.name', '')
            ));

            $replyTo = trim((string) (
                $params['replyTo']
                ?? ''
            ));

            if ($from === '') {
                throw new \InvalidArgumentException(
                    'MAIL_FROM_ADDRESS is not configured'
                );
            }

            $email = (new Email())
                ->from(new Address($from, $fromName))
                ->to($to)
                ->subject((string) $params['subject'])
                ->html((string) $params['html']);

            if ($replyTo !== '') {
                $email->replyTo($replyTo);
            }

            $email
                ->getHeaders()
                ->addTextHeader(
                    'X-MagicPro-Mail-ID',
                    $mailId
                );

            $rawMessage = $email->toString();

            $ses = new SesV2Client([
                'version' => 'latest',
                'region'  => (string) config('services.ses.region'),
                'credentials' => [
                    'key'    => (string) config('services.ses.key'),
                    'secret' => (string) config('services.ses.secret'),
                ],
            ]);

            $request = [
                'FromEmailAddress' => $from,
                'Destination' => [
                    'ToAddresses' => [$to],
                ],
                'Content' => [
                    'Raw' => [
                        'Data' => $rawMessage,
                    ],
                ],
            ];

            $configurationSet = trim((string) config(
                'services.ses.configuration_set',
                ''
            ));

            if ($configurationSet !== '') {
                $request['ConfigurationSetName'] = $configurationSet;
            }

            $result = $ses->sendEmail($request);

            return [
                'status'              => true,
                'mail_id'             => $mailId,
                'provider_message_id' => (string) $result->get('MessageId'),
                'raw_message'         => $rawMessage,
                'errorMsg'            => '',
            ];
        } catch (\Throwable $e) {
            return [
                'status'              => false,
                'mail_id'             => $mailId,
                'provider_message_id' => '',
                'raw_message'         => '',
                'errorMsg'            => $e->getMessage(),
            ];
        }
    }
}
