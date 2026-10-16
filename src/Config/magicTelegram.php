<?php

/**
 * Telegram of the package: the chat and the bot, both from .env.
 *
 * Read through config() and not by env() in the running code: after
 * `php artisan config:cache` Laravel stops loading .env, and every env() call
 * outside a config file turns into its default — the bot would go silent
 * without a word. A config file is read while the cache is built, so the
 * values survive it.
 *
 * Merged under `magicpro_telegram` by MagicServiceProvider. Both may stay
 * empty: then the site simply does not use telegram, and MproHelper::telegramSend()
 * says so instead of calling the api.
 */

return [
    // the chat every message goes to when the caller names none
    'chat_id' => trim((string) env('TELEGRAM_CHAT_ID', '')),

    // the bot that sends it
    'bot_token' => trim((string) env('TELEGRAM_BOT_TOKEN', '')),
];
