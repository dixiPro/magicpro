<?php

/**
 * Public addresses of the package itself — not the pages of the site.
 *
 * Loaded by MagicServiceProvider inside the `web` group, after admin.php and
 * before dynamic.php.
 */

use Illuminate\Support\Facades\Route;
use MagicProSrc\Api\API_SiteAuth;
use MagicProSrc\Config\MagicGlobals;
use MagicProSrc\Mail\AwsHookHandler;

$csrf = MagicGlobals::csrfMiddleware();

// AWS SES/SNS webhook: Amazon knows no form token, the signature of the message
// is checked inside the handler
Route::post('/awsHook', [AwsHookHandler::class, 'handle'])
    ->withoutMiddleware([$csrf])
    ->name('magic.awsHook');

// sign-up and sign-in of visitors: a form of the site posts here with its
// CSRF token, the page is in the same session
Route::post('/api/auth', [API_SiteAuth::class, 'handle'])
    ->name('magic.siteAuth');
