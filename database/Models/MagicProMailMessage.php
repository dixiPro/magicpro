<?php

namespace MagicProDatabaseModels; // в композере прописывается

use Illuminate\Database\Eloquent\Model;

/**
 * A single outgoing mail message.
 *
 * A letter always gets a row here, even a blocked one — see the STATUS_*
 * constants below and the mail service for the flow. The row stores both
 * the rendered html body and the full raw_message that goes to the provider.
 *
 * Retry timing is derived from `attempts` (0 -> +5m, 1 -> +15m, 2 -> +1h,
 * >=3 -> failed); there is no separate next_attempt_at column.
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
    public const STATUS_SENT         = 'sent';         // транспорт принял письмо (SES вернул MessageId)
    public const STATUS_DELIVERED    = 'delivered';    // доставлено (вебхук)
    public const STATUS_OPEN         = 'open';         // открыто пользователем (вебхук)
    public const STATUS_ERROR        = 'error';        // ошибка доставки, будет ретрай
    public const STATUS_FAILED       = 'failed';       // исчерпаны попытки отправки
    public const STATUS_EMAILBLOCKED = 'emailblocked'; // адрес заблокирован

    protected $fillable = [
        'provider_message_id',
        'mail_id',
        'from_email',
        'to_email',
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
