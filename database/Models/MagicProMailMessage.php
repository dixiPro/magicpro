<?php

namespace MagicProDatabaseModels; // в композере прописывается

use Illuminate\Database\Eloquent\Model;

/**
 * A single outgoing mail message.
 *
 * A row appears here when a letter reached the transport, or was queued for
 * later. What did not get that far leaves no row at all: an empty or malformed
 * address, a blocked one, and a duplicate are all refused by the api before a
 * message is created, and the caller learns about it from the error.
 *
 * So `emailblocked` is not the status of a refused attempt — it is the status
 * of a letter that had already been sent when a Bounce or a Complaint arrived
 * from the provider and blocked the address.
 *
 * The row stores both the rendered html body and the full raw_message that goes
 * to the provider.
 *
 * Retry timing is derived from `attempts` by API_Mail::nextSchedule()
 * (1 -> +5m, 2 -> +10m, 3 -> +30m, beyond that -> failed); there is no separate
 * next_attempt_at column.
 *
 * Delivery errors are appended to the `errors` JSON array via appendError().
 */
class MagicProMailMessage extends Model
{
    protected $table = 'magicPro_mail_messages';

    // ------------------------------------------------------------------
    // Statuses (single source of truth for the service, cron and admin
    // filter). delivered / open arrive later from AWS webhooks.
    // ------------------------------------------------------------------
    public const STATUS_QUEUED       = 'queued';       // ждёт отправки (sendLater / ретрай)
    public const STATUS_SENDING      = 'sending';      // взято в отправку, транспорт ещё не ответил
    public const STATUS_SENT         = 'sent';         // транспорт принял письмо (SES вернул MessageId)
    public const STATUS_DELIVERED    = 'delivered';    // доставлено (вебхук)
    public const STATUS_OPEN         = 'open';         // открыто пользователем (вебхук)
    public const STATUS_RETRYING     = 'retrying';      // ошибка доставки, будет ретрай
    public const STATUS_FAILED       = 'failed';       // исчерпаны попытки отправки
    public const STATUS_EMAILBLOCKED = 'emailblocked'; // адрес заблокирован

    protected $fillable = [
        'provider_message_id',
        'mail_id',
        'from_email',
        'from_name',
        'to_email',
        'reply_to',
        'subject',
        'html',
        'raw_message',
        'scheduled_at',
        'sent_at',
        'status',
        'errors',
        'attempts',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at'      => 'datetime',
        'errors'       => 'array',
        'attempts'     => 'integer',
    ];

    protected $attributes = [
        'status'   => self::STATUS_QUEUED,
        'attempts' => 0,
    ];

    /**
     * Append one error to the `errors` JSON array and persist it.
     * Errors accumulate (they are never overwritten), so the whole
     * delivery history of a letter stays in one field.
     *
     * $error is a free-form array, e.g.
     *     ['ts' => now()->toDateTimeString(), 'message' => $e->getMessage()]
     */
    public function appendError(array $error): void
    {
        $errors = $this->errors ?? [];
        $errors[] = $error;

        $this->errors = $errors;
        $this->save();
    }
}
