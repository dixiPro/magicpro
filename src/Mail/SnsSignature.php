<?php

namespace MagicProSrc\Mail;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Checks that an SNS envelope really came from Amazon.
 *
 * The webhook is a public route: no session, no csrf, no token — that is how a
 * webhook works. So the only thing that separates an event of ours from a
 * forged one is the signature, and until it is checked every knock has to be
 * treated as somebody else's.
 *
 * Amazon signs a canonical string built of the envelope fields, and hands the
 * certificate at `SigningCertURL`. Hence the two halves here: the address of
 * the certificate is checked (it must be Amazon's, otherwise the forger simply
 * signs with his own), and then the signature itself.
 *
 * `aws/aws-sdk-php` has `Sns\MessageValidator` for exactly this, but this
 * installation carries a trimmed build of the sdk without it, and adding a
 * dependency for one openssl call is not worth it.
 */
class SnsSignature
{
    /**
     * The host of SNS, and the only one trusted for both the certificate and
     * the address to knock back at. One rule for the two, because they are the
     * same service: a message whose certificate was accepted from the China
     * region used to have its SubscribeURL refused, and that is a difference
     * with no reason behind it.
     */
    private const SNS_HOST = '/^sns\.[a-z0-9-]+\.amazonaws\.com(\.cn)?$/';

    /** The certificate changes rarely and is the same for every event. */
    private const CERT_TTL = 86400;

    /** Fields of the canonical string, in the order Amazon signs them. */
    private const FIELDS = [
        'Notification' => ['Message', 'MessageId', 'Subject', 'Timestamp', 'TopicArn', 'Type'],
        'SubscriptionConfirmation' => ['Message', 'MessageId', 'SubscribeURL', 'Timestamp', 'Token', 'TopicArn', 'Type'],
        'UnsubscribeConfirmation' => ['Message', 'MessageId', 'SubscribeURL', 'Timestamp', 'Token', 'TopicArn', 'Type'],
    ];

    public static function valid(array $envelope): bool
    {
        $type = (string) ($envelope['Type'] ?? '');

        if (! isset(self::FIELDS[$type])) {
            return false;
        }

        $signature = base64_decode((string) ($envelope['Signature'] ?? ''), true);

        if ($signature === false || $signature === '') {
            return false;
        }

        $cert = self::certificate((string) ($envelope['SigningCertURL'] ?? ''));

        if ($cert === '') {
            return false;
        }

        $key = @openssl_pkey_get_public($cert);

        if ($key === false) {
            return false;
        }

        // version 2 is sha256, version 1 the old sha1; anything else is not ours
        $version = (string) ($envelope['SignatureVersion'] ?? '');

        $algorithm = match ($version) {
            '1' => OPENSSL_ALGO_SHA1,
            '2' => OPENSSL_ALGO_SHA256,
            default => null,
        };

        if ($algorithm === null) {
            return false;
        }

        return openssl_verify(self::canonical($envelope, $type), $signature, $key, $algorithm) === 1;
    }

    /**
     * The string Amazon signed: `field\nvalue\n` for each field it names, in
     * their order. A field that is absent from the envelope is skipped —
     * `Subject` of a notification is the usual case.
     */
    private static function canonical(array $envelope, string $type): string
    {
        $text = '';

        foreach (self::FIELDS[$type] as $field) {
            if (! isset($envelope[$field])) {
                continue;
            }

            $text .= $field . "\n" . $envelope[$field] . "\n";
        }

        return $text;
    }

    /**
     * The certificate by its url, checked and remembered for a day.
     *
     * The check of the address is the load-bearing part: without it a forger
     * points `SigningCertURL` at his own server, signs with his own key, and
     * every signature verifies perfectly.
     */
    private static function certificate(string $url): string
    {
        if (! self::snsUrl($url)) {
            return '';
        }

        $key  = 'magic:sns:cert:' . md5($url);
        $cert = (string) Cache::get($key, '');

        if ($cert !== '') {
            return $cert;
        }

        $answer = Http::timeout(5)->get($url);

        // only a certificate is remembered. A failed download used to be
        // remembered too, for a day: one 5xx of Amazon and every event was
        // refused until the next day, long after Amazon came back
        if (! $answer->successful()) {
            return '';
        }

        Cache::put($key, $answer->body(), self::CERT_TTL);

        return $answer->body();
    }

    /** An address to knock back at: the same check, so a subscription cannot be redirected. */
    public static function amazonUrl(string $url): bool
    {
        return self::snsUrl($url);
    }

    /** https on a host of SNS itself, nothing wider. */
    private static function snsUrl(string $url): bool
    {
        $parts = parse_url($url);

        return ($parts['scheme'] ?? '') === 'https'
            && (bool) preg_match(self::SNS_HOST, $parts['host'] ?? '');
    }
}
