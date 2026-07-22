<?php

namespace MagicProDatabaseModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Event latch: registers one-off events and tells whether they already happened.
 *
 * An event is identified by a string key and may have an expiration date.
 * addEvent() is a test-and-set: it both checks and registers in one call,
 * so the caller cannot forget to write the event after checking it.
 *
 * ------------------------------------------------------------------
 * Key
 * ------------------------------------------------------------------
 * Free-form string, by convention: type_subject_code
 *
 *     mail_user@site.com_registration
 *     help_42_17
 *
 * The key is normalized on every call (trim, lowercase, spaces removed),
 * so 'Mail_ User@Site.com' and 'mail_user@site.com' are the same event.
 *
 * Put a stable code into the key, not human-readable text: an email
 * subject may be reworded later, which would silently create a new key
 * and let the mail go out twice.
 *
 * The subject is an email for guests and a user id for registered users —
 * these are different keys for the same person, keep that in mind.
 *
 * ------------------------------------------------------------------
 * Usage — copy and paste
 * ------------------------------------------------------------------
 * Import the model once, at the top of the file:
 *
 *     use MagicProDatabaseModels\MagicProEvent;
 *
 * Send a registration mail at most once every 10 minutes:
 *
 *     $res = MagicProEvent::addEvent("mail_{$email}_registration", now()->addMinutes(10));
 *
 *     if ($res) {
 *         // true — no active event, the mail has to be sent
 *     } else {
 *         // false — already sent, the event has not expired yet
 *     }
 *
 * Remember a permanent user choice ('do not show this help again'):
 *
 *     $res = MagicProEvent::addEvent("help_{$userId}_17");
 *     // second argument omitted — the event never expires
 *
 * ------------------------------------------------------------------
 * Notes
 * ------------------------------------------------------------------
 * Renewing an expired event overwrites its row: created_at keeps the date
 * of the first registration, updated_at — of the last one. The history of
 * repeats is not stored.
 *
 * The key column is unique, so parallel requests cannot create duplicates.
 *
 * Expired rows are never deleted automatically. If the table ever grows
 * too big, delete them by hand:
 *
 *     DELETE FROM magicPro_events WHERE expires_at IS NOT NULL AND expires_at <= now
 */
class MagicProEvent extends Model
{
    protected $table = 'magicPro_events';

    protected $fillable = ['key', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Register an event.
     * Returns true if the event did not exist or has already expired
     * (the event is created / renewed), false if it is still active.
     *
     * $expiresAt: null — the event never expires.
     *
     * Example:
     *
     *     use MagicProDatabaseModels\MagicProEvent;
     *
     *     $res = MagicProEvent::addEvent("mail_{$email}_registration", now()->addMinutes(10));
     *
     *     if ($res) {
     *         // true — no active event, send the mail
     *     } else {
     *         // false — already sent, the event has not expired yet
     *     }
     *
     * Second argument — when the event expires:
     *
     *     now()->addMinutes(10) // Carbon
     *     '2026-12-31 23:59'    // string, parsed by Carbon
     *     null                  // omit it — the event never expires
     */
    public static function addEvent(string $key, Carbon|string|null $expiresAt = null): bool
    {
        $key = self::normalizeKey($key);

        if ($key === '') {
            throw new \InvalidArgumentException('Event key is required.');
        }

        $expiresAt = $expiresAt === null ? null : Carbon::parse($expiresAt);

        $event = self::where('key', $key)->first();

        // событие есть и не истекло — ничего не делаем
        if ($event && ($event->expires_at === null || $event->expires_at->isFuture())) {
            return false;
        }

        // нет события или истекло — создаём / продлеваем
        self::updateOrCreate(
            ['key' => $key],
            ['expires_at' => $expiresAt]
        );

        return true;
    }

    /**
     * Normalize an event key: trim, lowercase, remove inner spaces,
     * so that 'Mail_ User@Site.com' and 'mail_user@site.com' are the same event.
     */
    protected static function normalizeKey(string $key): string
    {
        return preg_replace('/\s+/u', '', mb_strtolower(trim($key)));
    }
}
