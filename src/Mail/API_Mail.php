<?php

namespace MagicProSrc\Mail;

use MagicProSrc\Mail\MagicProMailer;                 // makeEmail / send
use MagicProDatabaseModels\MagicProMailMessage;      // модель письма
use MagicProDatabaseModels\MagicProEmailAddress;     // реестр адресов / блокировка
use Illuminate\Support\Facades\Validator;

/**
 * Mail API of the subsystem, by the example of MagicProSrc\Api\API_Auth.
 *
 * Каждый метод работает «до первой ошибки»: как только что-то не так —
 * выбрасывает исключение. Родитель (AbstractMailApi::run) ловит его и
 * формирует отрицательный ответ status/errorMsg/data/request.
 *
 * Транспорт (MagicProMailer) уже готов: makeEmail() собирает MIME-письмо,
 * send() отправляет готовую строку. Ни один из них не бросает исключений —
 * они возвращают массив со status, и мы сами решаем, что делать.
 */
class API_Mail extends AbstractMailApi
{
    /**
     * Centralized error messages (dynamic parameters are omitted here and
     * built at throw site), so blade can display them consistently.
     */
    protected const ERRORS = [
        'email_required'            => 'to (email) required',
        'subject_too_short'         => 'subject must be at least 8 characters',
        'html_too_short'            => 'html must be at least 16 characters',
        'email_blocked'             => 'email blocked',
        'duplicate_email'           => 'duplicate email',
        'too_frequent'              => 'too frequent send to this address',
        'make_email_failed'         => 'failed to build email',
        'id_or_message_id_required' => 'id or MessageId required',
        'message_not_found'         => 'message not found',
        'attempts_exhausted'        => 'no more retry attempts',
        'send_failed'               => 'failed to send email',
    ];

    protected array $map = [
        'sendNow'            => 'sendNow',
        'sendLater'          => 'sendLater',
        'emaiQueue'          => 'emaiQueue',
        'deleteEmail'        => 'deleteEmail',
        'deleteQueueByEmail' => 'deleteQueueByEmail',
    ];

    // ==================================================================
    //                       helper methods
    // ==================================================================

    /**
     * Время следующей попытки отправки по номеру попытки.
     * 1 -> +5м, 2 -> +10м, 3 -> +30м, дальше — больше попыток нет (исключение).
     */
    public static function nextSchedule(int $attempts): \Illuminate\Support\Carbon
    {
        $timeAttempts = [
            1 => 5 * 60,
            2 => 10 * 60,
            3 => 30 * 60,
        ];

        if (!array_key_exists($attempts, $timeAttempts)) {
            throw new \Exception(self::ERRORS['attempts_exhausted']);
        }

        return now()->addSeconds($timeAttempts[$attempts]);
    }

    /**
     * Единая проверка адреса получателя: приводит к нижнему регистру,
     * требует непустое значение, проверяет блокировку в
     * magicPro_email_addresses. Возвращает нормализованный адрес.
     */
    protected static function checkEmail(string $email): string
    {
        $email = mb_strtolower(trim((string) ($email ?? '')));

        if ($email === '') {
            throw new \Exception(self::ERRORS['email_required']);
        }

        $address = MagicProEmailAddress::where('email', $email)
            ->where('blocked', true)
            ->first();

        if ($address) {
            $reason = trim((string) $address->block_reason);

            throw new \Exception(
                $reason !== '' ? $reason : self::ERRORS['email_blocked']
            );
        }

        return $email;
    }

    /**
     * Собирает и валидирует параметры письма для makeEmail(): to, subject,
     * html, from, fromName, replyTo. Общие правила для sendNow и sendLater —
     * subject не короче 8 символов, html не короче 16.
     */
    protected static function buildLetterParams(array $params): array
    {
        $letterParams = [
            'to'       => self::checkEmail($params['to']),
            'subject'  => (string) ($params['subject'] ?? ''),
            'html'     => (string) ($params['html'] ?? ''),
            // пустая строка не запускает фолбэк на mail.from.address внутри
            // makeEmail() (там `??`, а не `?:`), поэтому подставляем сюда сами
            'from'     => trim((string) ($params['from'] ?? '')) ?: (string) config('mail.from.address', ''),
            'fromName' => trim((string) ($params['fromName'] ?? '')) ?: (string) config('mail.from.name', ''),
            'replyTo'  => trim((string) ($params['replyTo'] ?? '')),
        ];
        $replyTo = trim((string) ($params['replyTo'] ?? ''));

        if ($replyTo !== '') {
            Validator::make(
                ['email' => $replyTo],               // что проверяем
                ['email' => ['required', 'email']] // как проверяем
            )->validate();
            $letterParams['replyTo'] = $replyTo;
        }

        $validator = Validator::make($letterParams, [
            'subject' => ['string', 'min:8'],
            'html'    => ['string', 'min:16'],
        ], [
            'subject.min' => self::ERRORS['subject_too_short'],
            'html.min'    => self::ERRORS['html_too_short'],
        ]);

        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }

        return $letterParams;
    }

    /**
     * Ищет дубли по паре to + subject (берём последнее письмо на этот адрес
     * с этой темой).
     *   - если такое письмо есть и его статус НЕ sent — 'duplicate email';
     *   - если статус sent, но с момента sent_at прошло меньше
     *     retryTimeEmail секунд (по умолчанию 60) — 'too frequent'.
     */
    protected static function findDduplicates(array $params): void
    {
        $email = mb_strtolower(
            trim((string) ($params['to'] ?? ''))
        );

        $validated = Validator::make(
            ['email' => $email],
            ['email' => ['required', 'email'],]
        )->validate();

        $to      = $email;
        $subject = (string) ($params['subject'] ?? '');

        $last = MagicProMailMessage::query()
            ->where('to_email', $to)
            ->where('subject', $subject)
            ->orderByDesc('id')
            ->first();

        if (!$last) {
            return;
        }

        if ($last->status !== MagicProMailMessage::STATUS_SENT) {
            throw new \Exception(self::ERRORS['duplicate_email']);
        }

        $retryTime = (int) env('retryTimeEmail', 60);

        if (
            $last->sent_at
            && $last->sent_at->diffInSeconds(now(), true) < $retryTime
        ) {
            throw new \Exception(self::ERRORS['too_frequent']);
        }
    }

    // ==================================================================
    //                        public commands
    // ==================================================================

    /**
     * Сделать письмо и положить его в базу (реально отправит крон).
     * Params: from?, fromName?, to, replyTo?, subject, html, scheduled_at?.
     */
    protected static function sendLater(array $params): array
    {
        $letterParams = self::buildLetterParams($params);
        self::findDduplicates($params);

        $message = MagicProMailMessage::create([
            'from_email'   => $letterParams['from'],
            'to_email'     => $letterParams['to'],
            'subject'      => $letterParams['subject'],
            'html'         => $letterParams['html'],
            'scheduled_at' => $params['scheduled_at'] ?? null,
            'status'       => MagicProMailMessage::STATUS_QUEUED,
            'raw_message'  => '',

        ]);

        return [
            'id'           => $message->id,
            'status'       => $message->status,
            'scheduled_at' => (string) $message->scheduled_at,
        ];
    }

    /**
     * Отправить письмо мгновенно.
     * Params: from?, fromName?, to, replyTo?, subject (>= 8), html (>= 16).
     */
    protected static function sendNow(array $params): array
    {
        $letterParams = self::buildLetterParams($params);
        self::findDduplicates($params);

        $sent = MagicProMailer::send($letterParams);

        if ($sent['status']) {
            $message = MagicProMailMessage::create([
                'mail_id'              => $sent['mail_id'],
                'from_email'           => $letterParams['from'],
                'to_email'             => $letterParams['to'],
                'subject'              => $letterParams['subject'],
                'html'                 => $letterParams['html'],
                'raw_message'          => $sent['raw_message'],
                'provider_message_id'  => $sent['provider_message_id'],
                'status'               => MagicProMailMessage::STATUS_SENT,
                'sent_at'              => now(),
                'attempts'             => 1,
            ]);

            return [
                'id'                  => $message->id,
                'mail_id'             => $message->mail_id,
                'provider_message_id' => $message->provider_message_id,
                'status'              => $message->status,
            ];
        }

        throw new \Exception($sent['errorMsg'] ?: self::ERRORS['send_failed']);
    }

    /**
     * Список писем в очереди для указанного email.
     * Очередь — письма, ещё не отправленные (queued / error).
     * Params: to (email).
     */
    protected static function emaiQueue(array $params): array
    {
        $email = mb_strtolower(
            trim((string) ($params['email'] ?? ''))
        );

        $validated = Validator::make(
            ['email' => $email],
            ['email' => ['required', 'email'],]
        )->validate();

        $messages = MagicProMailMessage::query()
            ->where('to_email', $email)
            ->whereIn('status', [
                MagicProMailMessage::STATUS_QUEUED,
                MagicProMailMessage::STATUS_ERROR,
            ])
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->get([
                'id',
                'mail_id',
                'to_email',
                'subject',
                'status',
                'scheduled_at',
                'attempts',
                'created_at',
            ]);

        return [
            'to'    => $email,
            'queue' => $messages->all(),
        ];
    }

    /**
     * Удалить письмо по id или по Message-ID (provider_message_id, с запасным
     * поиском по собственному mail_id).
     * Params: id | MessageId.
     */
    protected static function deleteEmail(array $params): array
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
    protected static function deleteQueueByEmail(array $params): array
    {
        $email = mb_strtolower(
            trim((string) ($params['email'] ?? ''))
        );

        $validated = Validator::make(
            ['email' => $email],
            ['email' => ['required', 'email'],]
        )->validate();

        $email = $validated['email'];

        $deleted = MagicProMailMessage::query()
            ->where('to_email', $email)
            ->whereIn('status', [
                MagicProMailMessage::STATUS_QUEUED,
            ])
            ->delete();

        return [
            'to'      => $email,
            'deleted' => $deleted,
        ];
    }
}
