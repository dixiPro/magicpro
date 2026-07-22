<?php

/**
 * Отправка ОДНОГО реального письма транспортом MagicProMailer.
 *
 * Шлёт по-настоящему, через драйвер из .env (MAIL_MAILER = SES SMTP).
 * Ничего не подменяет. Это ручная проверка реальной доставки —
 * в отличие от php artisan test, где MAIL_MAILER принудительно = array
 * (phpunit.xml) и письмо никуда не уходит.
 *
 * Перед запуском поправь 'to' на свой адрес.
 *
 * Запуск из корня проекта:
 *
 *     php artisan tinker packages/dixipro/magicpro/src/test/send_one.php
 */

use MagicProSrc\Mail\MagicProMailer;

$result = (new MagicProMailer())->send([
    'to'      => 'dixi.ru@gmail.com',
    'subject' => 'MagicPro test letter',
    'html'    => '<p>Тестовое письмо из MagicProMailer.</p>',
]);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n";

echo $result['status']
    ? "OK: письмо отправлено, provider_message_id={$result['provider_message_id']}\n"
    : "ОШИБКА: {$result['errorMsg']}\n";
