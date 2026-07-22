<?php

use MagicProSrc\Mail\API_Mail;
use MagicProDatabaseModels\MagicProMailMessage;
use MagicProDatabaseModels\MagicProEmailAddress;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use MagicProSrc\Mail\MagicProMailer;

/*
php artisan test packages/dixipro/magicpro/src/test/API_MailTest.php
*/

class API_MailTest extends TestCase
{

    public function test_send_now_real_email_by_aws_api(): void
    {
        $result = MagicProMailer::sendByAwsApi([
            'to'      => 'dixi.ru@gmail.com',
            'subject' => 'AWS SES API real test',
            'html'    => '<p>Real email test through AWS SES API.</p>',
        ]);

        $this->assertTrue(
            $result['status'],
            $result['errorMsg'] ?? ''
        );

        $this->assertNotEmpty($result['mail_id'] ?? null);
        $this->assertNotEmpty($result['provider_message_id'] ?? null);
        $this->assertNotEmpty($result['raw_message'] ?? null);
    }

    public function test_send_now_real_email(): void
    {
        config([
            'mail.default' => 'smtp',
            'database.connections.sqlite.database' => database_path('database.sqlite'),
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $result = API_Mail::run('sendNow', [
            'to'      => 'dixi.ru@gmail.com',
            'subject' => 'API_Mail real test',
            'html'    => '<p>Real email test.</p>',
        ]);

        $this->assertTrue($result['status'], $result['errorMsg'] ?? '');
        $this->assertNotEmpty($result['data']['mail_id'] ?? null);
    }

    public function test_send_later_queues_message(): void
    {
        config([
            'database.connections.sqlite.database' => database_path('database.sqlite'),
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $result = API_Mail::run('sendLater', [
            'to'      => 'dixi.ru@gmail.com',
            'subject' => 'API_Mail sendLater test',
            'html'    => '<p>sendLater test body.</p>',
        ]);

        $this->assertTrue($result['status'], $result['errorMsg'] ?? '');
        $this->assertSame(MagicProMailMessage::STATUS_QUEUED, $result['data']['status']);

        $this->assertDatabaseHas('magicPro_mail_messages', [
            'id'     => $result['data']['id'],
            'status' => MagicProMailMessage::STATUS_QUEUED,
        ]);
    }

    public function test_send_later_stores_scheduled_at(): void
    {
        config([
            'database.connections.sqlite.database' => database_path('database.sqlite'),
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $scheduledAt = now()->addDay()->toDateTimeString();

        $result = API_Mail::run('sendLater', [
            'to'           => 'dixi.ru@gmail.com',
            'subject'      => 'API_Mail sendLater scheduled_at',
            'html'         => '<p>sendLater scheduled_at test.</p>',
            'scheduled_at' => $scheduledAt,
        ]);

        $this->assertTrue($result['status'], $result['errorMsg'] ?? '');

        $this->assertDatabaseHas('magicPro_mail_messages', [
            'id'           => $result['data']['id'],
            'scheduled_at' => $scheduledAt,
        ]);
    }

    public function test_email_queue_returns_queued_message(): void
    {
        config([
            'database.connections.sqlite.database' => database_path('database.sqlite'),
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $queued = API_Mail::run('sendLater', [
            'to'      => 'dixi.ru@gmail.com',
            'subject' => 'API_Mail emaiQueue subject',
            'html'    => '<p>emaiQueue test body.</p>',
        ]);
        $this->assertTrue($queued['status'], $queued['errorMsg'] ?? '');

        $result = API_Mail::run('emaiQueue', ['email' => 'dixi.ru@gmail.com']);

        $this->assertTrue($result['status'], $result['errorMsg'] ?? '');
        $ids = array_column($result['data']['queue'], 'id');

        $this->assertContains($queued['data']['id'], $ids);
    }

    public function test_delete_email_by_id(): void
    {
        config([
            'database.connections.sqlite.database' => database_path('database.sqlite'),
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $created = API_Mail::run('sendLater', [
            'to'      => 'dixi.ru@gmail.com',
            'subject' => 'API_Mail deleteEmail subject',
            'html'    => '<p>deleteEmail test body.</p>',
        ]);
        $this->assertTrue($created['status'], $created['errorMsg'] ?? '');

        $result = API_Mail::run('deleteEmail', ['id' => $created['data']['id']]);

        $this->assertTrue($result['status'], $result['errorMsg'] ?? '');
        $this->assertDatabaseMissing('magicPro_mail_messages', [
            'id' => $created['data']['id'],
        ]);
    }

    public function test_delete_email_fails_when_no_identifier(): void
    {
        $result = API_Mail::run('deleteEmail', []);

        $this->assertFalse($result['status']);
        $this->assertSame('id or MessageId required', $result['errorMsg']);
    }

    public function test_delete_queue_by_email_removes_queued(): void
    {
        config([
            'database.connections.sqlite.database' => database_path('database.sqlite'),
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $queued = API_Mail::run('sendLater', [
            'to'      => 'dixi.ru@gmail.com',
            'subject' => 'API_Mail deleteQueueByEmail subject',
            'html'    => '<p>deleteQueueByEmail test body.</p>',
        ]);
        $this->assertTrue($queued['status'], $queued['errorMsg'] ?? '');

        $result = API_Mail::run('deleteQueueByEmail', ['email' => 'dixi.ru@gmail.com']);

        $this->assertTrue($result['status'], $result['errorMsg'] ?? '');
        $this->assertGreaterThanOrEqual(1, $result['data']['deleted']);

        $this->assertDatabaseMissing('magicPro_mail_messages', [
            'id' => $queued['data']['id'],
        ]);
    }
}
