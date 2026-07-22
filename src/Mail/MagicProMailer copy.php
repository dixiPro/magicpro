<?php

namespace MagicProSrc\Mail;

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
}
