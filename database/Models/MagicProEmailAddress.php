<?php

namespace MagicProDatabaseModels; // в композере прописывается

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Email address registry for the mail subsystem.
 *
 * Data may arrive here not only from the mailer (e.g. an ip is stored
 * alongside the address), so blocking lives in a separate table rather
 * than on the message.
 *
 * Every outgoing letter checks isBlocked() before sending; a blocked
 * address still gets a row in magicPro_mail_messages, but with the
 * emailblocked status (see the mail service).
 */
class MagicProEmailAddress extends Model
{
    protected $table = 'magicPro_email_addresses';

    protected $fillable = [
        'email',
        'ip_address',
        'blocked',
        'block_reason',
        'blocked_at',
    ];

    protected $casts = [
        'blocked'    => 'boolean',
        'blocked_at' => 'datetime',
    ];

    protected $attributes = [
        'blocked' => false,
    ];

    // === email always normalized: trimmed and lowercased ===
    public function setEmailAttribute($v): void
    {
        $this->attributes['email'] = mb_strtolower(trim((string) ($v ?? '')));
    }

    /**
     * Whether the given email is currently blocked.
     * The email is normalized the same way as on write.
     */
    public static function isBlocked(string $email): bool
    {
        $email = mb_strtolower(trim($email));

        if ($email === '') {
            return false;
        }

        return self::where('email', $email)
            ->where('blocked', true)
            ->exists();
    }

    /**
     * Block an address (creating its row if missing).
     * Called from the webhook handler (bounce / complaint) and manually.
     * Returns the address row.
     */
    public static function block(string $email, string $reason = ''): self
    {
        $email = mb_strtolower(trim($email));

        return self::updateOrCreate(
            ['email' => $email],
            [
                'blocked'      => true,
                'block_reason' => $reason,
                'blocked_at'   => Carbon::now(),
            ]
        );
    }
}
