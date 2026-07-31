<?php

namespace MagicProSrc\Mail;

use Aws\SesV2\SesV2Client;
use MagicProDatabaseModels\MagicProMailMessage;      // модель письма
use MagicProDatabaseModels\MagicProEmailAddress;     // реестр адресов / блокировка
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Mail API of the subsystem, by the example of MagicProSrc\Api\API_Auth.
 *
 * Каждый метод работает «до первой ошибки»: как только что-то не так —
 * выбрасывает исключение. Родитель (AbstractMailApi::run) ловит его и
 * формирует отрицательный ответ status/errorMsg/data/request.
 *
 * Транспорт: sendBySmtp() / sendByAwsApi() собирают MIME-письмо и отправляют его.
 * Ни один из них не бросает исключений — они возвращают массив со status,
 * и мы сами решаем, что делать.
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
        'sendQueue'          => 'sendQueue',
        'emailQueue'          => 'emailQueue',
        'messagesList'       => 'messagesList',
        'addressesList'      => 'addressesList',
        'deleteEmail'        => 'deleteEmail',
        'deleteQueueByEmail' => 'deleteQueueByEmail',
    ];

    /**
     * Статусы «очереди» — письма, которые ещё будут отправлены (queued) или
     * ждут ретрая (retrying). Всё остальное считается «отправленным» для админки.
     */
    protected const QUEUE_STATUSES = [
        MagicProMailMessage::STATUS_QUEUED,
        MagicProMailMessage::STATUS_RETRYING,
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
        $address = MagicProEmailAddress::where('email', $email)->first();
        if (!$address) {
            $address = MagicProEmailAddress::create([
                'email'      => $email,
                'ip_address' => request()->ip(),
            ]);
        }

        if ($address->blocked) {
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

        if (($params['scheduled_at'] ?? '') === '') {
            $params['scheduled_at'] = now()->addSeconds(60);
        }

        $message = MagicProMailMessage::create([
            'from_name'    => $letterParams['fromName'],
            'from_email'   => $letterParams['from'],
            'to_email'     => $letterParams['to'],
            'reply_to'     => $letterParams['replyTo'],
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

        $SesV2Client = env('AWS_SesV2Client', false);
        if ($SesV2Client) {
            $sent = self::sendByAwsApi($letterParams);
        } else {
            $sent = self::sendBySmtp($letterParams);
        }

        if ($sent['status']) {
            $message = MagicProMailMessage::create([
                'mail_id'              => $sent['mail_id'],
                'from_email'           => $letterParams['from'],
                'from_name'            => $letterParams['fromName'],
                'to_email'             => $letterParams['to'],
                'reply_to'             => $letterParams['replyTo'],
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
                '$SesV2Client'        => $SesV2Client
            ];
        }

        MagicProMailMessage::create([
            'mail_id'      => $sent['mail_id'],
            'from_email'   => $letterParams['from'],
            'from_name'    => $letterParams['fromName'],
            'to_email'     => $letterParams['to'],
            'reply_to'     => $letterParams['replyTo'],
            'subject'      => $letterParams['subject'],
            'html'         => $letterParams['html'],
            'raw_message'  => $sent['raw_message'],
            'status'       => MagicProMailMessage::STATUS_RETRYING,
            'attempts'     => 1,
            'errors'       => [[
                'ts'      => now()->toDateTimeString(),
                'message' => $sent['errorMsg'],
            ]],
        ]);

        throw new \Exception($sent['errorMsg'] ?: self::ERRORS['send_failed']);
    }

    /**
     * Отправляет письма из очереди: status = queued и время пришло
     * (scheduled_at пуст или уже наступил). Каждое письмо отправляется
     * через self::sendBySmtp(). Ошибка одного письма не прерывает
     * обработку остальных.
     *
     * При ошибке отправки: attempts++, статус retrying, следующая попытка
     * через nextSchedule(attempts). Когда попытки исчерпаны — статус failed.
     */
    protected static function sendQueue(array $params): array
    {
        $lock = Cache::lock('API_Mail::sendQueue', 300);

        if (!$lock->get()) {
            return [
                'total'  => 0,
                'sent'   => 0,
                'failed' => 0,
            ];
        }

        try {
            return self::sendQueueLocked($params);
        } finally {
            $lock->release();
        }
    }

    protected static function sendQueueLocked(array $params): array
    {
        $messages = MagicProMailMessage::query()
            ->whereIn('status', [
                MagicProMailMessage::STATUS_QUEUED,
                MagicProMailMessage::STATUS_RETRYING,
            ])
            ->where(function ($q) {
                $q->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            })
            ->get();

        $sentCount   = 0;
        $failedCount = 0;

        foreach ($messages as $message) {
            $startedAt = microtime(true);

            $sent = self::sendBySmtp([
                'to'       => $message->to_email,
                'subject'  => $message->subject,
                'html'     => $message->html,
                'from'     => $message->from_email,
                'fromName' => $message->from_name ?? '',
                'replyTo'  => $message->reply_to ?? '',
                'mail_id'  => $message->mail_id ?: null,
            ]);

            dump('sendQueue message #' . $message->id . ': ' . round((microtime(true) - $startedAt) * 1000) . 'ms');

            if ($sent['status']) {
                // успешная отправка
                $message->update([
                    'mail_id'              => $sent['mail_id'],
                    'raw_message'          => $sent['raw_message'],
                    'provider_message_id'  => $sent['provider_message_id'],
                    'status'               => MagicProMailMessage::STATUS_SENT,
                    'sent_at'              => now(),
                    'attempts'             => $message->attempts + 1,
                ]);

                $sentCount++;
                continue;
            }

            $failedCount++;
            $attempts = $message->attempts + 1;

            try {
                $scheduledAt = self::nextSchedule($attempts);
                // есть новая дата отправки

                $message->update([
                    'mail_id'      => $sent['mail_id'],
                    'raw_message'  => $sent['raw_message'],
                    'status'       => MagicProMailMessage::STATUS_RETRYING,
                    'attempts'     => $attempts,
                    'scheduled_at' => $scheduledAt,
                ]);
            } catch (\Throwable $e) {
                // повторная отправка невозможна, например количество повторов превышено
                $message->update([
                    'mail_id'     => $sent['mail_id'],
                    'raw_message' => $sent['raw_message'],
                    'status'      => MagicProMailMessage::STATUS_FAILED,
                    'attempts'    => $attempts,
                ]);
            }

            $message->appendError([
                'ts'      => now()->toDateTimeString(),
                'message' => $sent['errorMsg'],
            ]);
        }

        return [
            'total'  => $messages->count(),
            'sent'   => $sentCount,
            'failed' => $failedCount,
        ];
    }

    /**
     * Список писем в очереди для указанного email.
     * Очередь — письма, ещё не отправленные (queued / retrying).
     * Params: to (email).
     */
    protected static function emailQueue(array $params): array
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
                MagicProMailMessage::STATUS_RETRYING,
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
            'queue' => $messages->toArray(),
        ];
    }

    /**
     * Список писем для админки с разбивкой по разделам и пагинацией.
     *   - section = 'queue'  -> статусы queued + retrying (см. QUEUE_STATUSES);
     *   - section = 'sent'   -> все остальные статусы;
     * Поиск — подстрока по to_email. Пагинация — count (по умолчанию 30,
     * максимум 200) + offset. errors отдаём сразу, чтобы модалка ошибок
     * не делала лишний запрос.
     * Params: section, search?, count?, offset?.
     */
    protected static function messagesList(array $params): array
    {
        $section = ($params['section'] ?? 'sent') === 'queue' ? 'queue' : 'sent';
        $search  = mb_strtolower(trim((string) ($params['search'] ?? '')));

        $count = (int) ($params['count'] ?? 30);
        if ($count < 1) {
            $count = 30;
        }

        $offset = max(0, (int) ($params['offset'] ?? 0));

        $query = MagicProMailMessage::query();

        if ($section === 'queue') {
            $query->whereIn('status', self::QUEUE_STATUSES);
        } else {
            $query->whereNotIn('status', self::QUEUE_STATUSES);
        }

        if ($search !== '') {
            $query->where('to_email', 'like', '%' . $search . '%');
        }

        $total = (clone $query)->count();

        if ($section === 'queue') {
            $query->orderBy('scheduled_at')->orderBy('id');
        } else {
            $query->orderByDesc('sent_at')->orderByDesc('id');
        }

        $messages = $query
            ->offset($offset)
            ->limit($count)
            ->get([
                'id',
                'from_email',
                'from_name',
                'to_email',
                'reply_to',
                'subject',
                'html',
                'raw_message',
                'sent_at',
                'scheduled_at',
                'attempts',
                'status',
                'errors',
                'updated_at',
            ]);

        return [
            'section'  => $section,
            'total'    => $total,
            'count'    => $count,
            'offset'   => $offset,
            'messages' => $messages->all(),
        ];
    }

    /**
     * Список адресов (magicPro_email_addresses) для админки: поиск по email +
     * пагинация. Params: search + count (по умолчанию 30) + offset.
     */
    protected static function addressesList(array $params): array
    {
        $search = mb_strtolower(trim((string) ($params['search'] ?? '')));

        $count = (int) ($params['count'] ?? 30);
        if ($count < 1) {
            $count = 30;
        }

        $offset = max(0, (int) ($params['offset'] ?? 0));

        $query = MagicProEmailAddress::query();

        if ($search !== '') {
            $query->where('email', 'like', '%' . $search . '%');
        }

        $total = (clone $query)->count();

        $addresses = $query
            ->orderBy('email')
            ->offset($offset)
            ->limit($count)
            ->get([
                'id',
                'email',
                'ip_address',
                'blocked',
                'block_reason',
                'blocked_at',
                'updated_at',
            ]);

        return [
            'total'     => $total,
            'count'     => $count,
            'offset'    => $offset,
            'addresses' => $addresses->all(),
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
     * Удалить все письма в очереди (queued / retrying) для указанного email.
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
    protected static function sendBySmtp(array $params): array
    {
        $mailId = (string) (
            $params['mail_id']
            ?? Str::uuid()
        );

        try {
            $email = (new Email())
                ->from(new Address($params['from'], $params['fromName']))
                ->to($params['to'])
                ->subject($params['subject'])
                ->html($params['html']);

            if ($params['replyTo'] !== '') {
                $email->replyTo($params['replyTo']);
            }

            $email
                ->getHeaders()
                ->addTextHeader(
                    'X-MagicPro-Mail-ID',
                    $mailId
                );

            $configurationSet = trim((string) env(
                'AWS_SES_CONFIGURATION_SET',
                ''
            ));

            if ($configurationSet !== '') {
                $email->getHeaders()->addTextHeader(
                    'X-SES-CONFIGURATION-SET',
                    $configurationSet
                );
            }

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
    protected static function sendByAwsApi(array $params): array
    {
        $mailId = (string) (
            $params['mail_id']
            ?? Str::uuid()
        );

        try {
            $email = (new Email())
                ->from(new Address($params['from'], $params['fromName']))
                ->to($params['to'])
                ->subject($params['subject'])
                ->html($params['html']);

            if ($params['replyTo'] !== '') {
                $email->replyTo($params['replyTo']);
            }

            $email
                ->getHeaders()
                ->addTextHeader('X-MagicPro-Mail-ID', $mailId);

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
                'FromEmailAddress' => $params['from'],
                'Destination' => [
                    'ToAddresses' => [$params['to']],
                ],
                'Content' => [
                    'Raw' => [
                        'Data' => $rawMessage,
                    ],
                ],
            ];

            $configurationSet = trim((string) env('AWS_SES_CONFIGURATION_SET', ''));

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
