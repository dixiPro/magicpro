<?php

namespace Tests\Feature;

use MagicProSrc\Mail\MagicProMailer;
use Tests\TestCase;
/*
php artisan test packages/dixipro/magicpro/src/test/MagicProMailerTest.php
*/

class MagicProMailerTest extends TestCase
{
    public function test_send_real_email(): void
    {
        // В testing по умолчанию используется array.
        config([
            'mail.default' => 'smtp',
        ]);

        $result = MagicProMailer::send([
            'to'      => 'dixi.ru@gmail.com',
            'subject' => 'MagicProMailer real test',
            'html'    => '<p>Реальная тестовая отправка из Laravel.</p>',
        ]);

        $this->assertTrue(
            $result['status'],
            $result['errorMsg']
        );

        $this->assertNotEmpty(
            $result['mail_id']
        );

        $this->assertNotEmpty(
            $result['provider_message_id']
        );

        $this->assertNotEmpty(
            $result['raw_message']
        );

        $this->assertSame(
            '',
            $result['errorMsg']
        );

        dump($result);
    }

    public function test_send_real_email_by_aws_api(): void
    {
        $result = MagicProMailer::sendByAwsApi([
            'to'      => 'dixi.ru@gmail.com',
            'subject' => 'MagicProMailer AWS API real test',
            'html'    => '<p>Реальная тестовая отправка через AWS SES API.</p>',
        ]);

        $this->assertTrue(
            $result['status'],
            $result['errorMsg']
        );

        $this->assertNotEmpty(
            $result['mail_id']
        );

        $this->assertNotEmpty(
            $result['provider_message_id']
        );

        $this->assertNotEmpty(
            $result['raw_message']
        );

        $this->assertSame(
            '',
            $result['errorMsg']
        );

        dump($result);
    }
}
