<?php

/**
 * Mail settings of the package that live in .env, read through config().
 *
 * `env()` outside a config file sees nothing after `php artisan config:cache`:
 * Laravel stops loading .env, and every `env('AWS_...')` in the running code
 * turned into its default. On a production site that meant mail silently moving
 * from the SES API to SMTP and SES events no longer tied to the letters. A
 * config file is read while the cache is built, so the values survive it.
 *
 * Merged under `magicpro_mail` by MagicServiceProvider. The site overrides
 * nothing here — the values come from .env as before.
 */

return [
    // true — letters go through the SES API v2, otherwise through SMTP
    'ses_api' => (bool) env('AWS_SesV2Client', false),

    // SES configuration set: the one that sends delivery and open events
    'configuration_set' => trim((string) env('AWS_SES_CONFIGURATION_SET', '')),

    // SNS topic the webhook takes events from; empty — any topic
    'sns_topic_arn' => trim((string) env('AWS_SNS_TOPIC_ARN', '')),

    // seconds between two letters with the same address and subject
    'retry_time' => (int) env('retryTimeEmail', 60),
];
