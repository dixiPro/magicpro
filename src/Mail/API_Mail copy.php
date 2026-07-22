<?php

namespace MagicProSrc\Mail\Api;

use MagicProSrc\Mail\Service\MailService;
use MagicProDatabaseModels\MagicProMailMessage;

/**
 * Mail API of the subsystem, by the example of MagicProSrc\Api\API_Auth.
 *
 * Thin wrappers over MailService — dispatch via $map, params as a plain array,
 * errors thrown as exceptions, standard status/errorMsg/data/request response
 * (built by AbstractMailApi::run).
 */
class API_Mail extends AbstractMailApi
{
    /**
     * Centralized error messages (dynamic parameters are omitted here and
     * built at throw site), so blade can display them consistently.
     */
    protected const ERRORS = [
        'id_or_message_id_required' => 'id or MessageId required',
        'email_required'            => 'to (email) required',
        'message_not_found'         => 'message not found',
    ];

    protected array $map = [
        'sendNow'            => 'sendNow',
        'sendLater'          => 'sendLater',
        'emaiQueue'          => 'emaiQueue',
        'deleteEmail'        => 'deleteEmail',
        'deleteQueueByEmail' => 'deleteQueueByEmail',
    ];

    protected function service(): MailService
    {
        return new MailService();
    }

    /**
     * Отправить письмо сейчас. Кладёт в таблицу и письмо, и результат.
     * Params: from?, fromName?, to, replyTo?, subject, html.
     */
    protected function sendNow(array $params): array
    {
        $message = $this->service()->sendNow($params);

        return [
            'id'                  => $message->id,
            'mail_id'             => $message->mail_id,
            'provider_message_id' => $message->provider_message_id,
            'status'              => $message->status,
        ];
    }

    /**
     * Поставить письмо в очередь на дату (реально отправит крон).
     * Params: from?, fromName?, to, replyTo?, subject, html, scheduled_at.
     */
    protected function sendLater(array $params): array
    {
        $message = $this->service()->sendLater($params);

        return [
            'id'           => $message->id,
            'mail_id'      => $message->mail_id,
            'status'       => $message->status,
            'scheduled_at' => (string) $message->scheduled_at,
        ];
    }

    /**
     * Список писем в очереди для указанного email.
     * Очередь — письма, ещё не отправленные (queued / error).
     * Params: to (email).
     */
    protected function emaiQueue(array $params): array
    {
        $to = mb_strtolower(trim((string) ($params['to'] ?? '')));

        if ($to === '') {
            throw new \Exception(self::ERRORS['email_required']);
        }

        $messages = MagicProMailMessage::query()
            ->where('to_email', $to)
            ->whereIn('status', [
                MagicProMailMessage::STATUS_QUEUED,
                MagicProMailMessage::STATUS_ERROR,
            ])
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->get(['id', 'mail_id', 'to_email', 'subject', 'status', 'scheduled_at', 'attempts', 'created_at']);

        return [
            'to'    => $to,
            'queue' => $messages->all(),
        ];
    }

    /**
     * Удалить письмо по id или по MessageId (provider_message_id / mail_id).
     * Params: id | MessageId.
     */
    protected function deleteEmail(array $params): array
    {
        $id        = (int) ($params['id'] ?? 0);
        $messageId = trim((string) ($params['MessageId'] ?? $params['message_id'] ?? ''));

        if ($id <= 0 && $messageId === '') {
            throw new \Exception(self::ERRORS['id_or_message_id_required']);
        }

        $query = MagicProMailMessage::query();

        if ($id > 0) {
            $query->where('id', $id);
        } else {
            $query->where(function ($q) use ($messageId) {
                $q->where('provider_message_id', $messageId)
                    ->orWhere('mail_id', $messageId);
            });
        }

        $message = $query->first();

        if (!$message) {
            throw new \Exception(self::ERRORS['message_not_found']);
        }

        $deletedId = $message->id;
        $message->delete();

        return [
            'deleted' => true,
            'id'      => $deletedId,
        ];
    }

    /**
     * Удалить все письма в очереди (queued / error) для указанного email.
     * Params: to (email).
     */
    protected function deleteQueueByEmail(array $params): array
    {
        $to = mb_strtolower(trim((string) ($params['to'] ?? '')));

        if ($to === '') {
            throw new \Exception(self::ERRORS['email_required']);
        }

        $deleted = MagicProMailMessage::query()
            ->where('to_email', $to)
            ->whereIn('status', [
                MagicProMailMessage::STATUS_QUEUED,
                MagicProMailMessage::STATUS_ERROR,
            ])
            ->delete();

        return [
            'to'      => $to,
            'deleted' => $deleted,
        ];
    }
}
