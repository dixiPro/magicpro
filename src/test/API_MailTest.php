<?php

use MagicProSrc\Mail\API_Mail;
use MagicProDatabaseModels\MagicProMailMessage;
use MagicProDatabaseModels\MagicProEmailAddress;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use MagicProSrc\Mail\MagicProMailer;

/*
php artisan test packages/dixipro/magicpro/src/test/API_MailTest.php

Tests here are real ("боевые"): real database/database.sqlite, real mail
transport, no mocks and no RefreshDatabase, unless explicitly requested
otherwise.
*/

class API_MailTest extends TestCase
{
    // private const TEST_EMAIL = 'dixi.ru@gmail.com';
    private const TEST_EMAIL = 'fin@solnushkov.ru';

    public function test_send_now(): void
    {
        config([
            'mail.default' => 'smtp',
            'database.connections.sqlite.database' => database_path('database.sqlite'),
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $result = API_Mail::run('sendNow', [
            'to'      => self::TEST_EMAIL,
            'subject' => 'API_Mail smtp real test ' . now(),
            'html'    => '<p>Real email test.</p>',
            'fromName' => 'testName',
            'replyTo' => 'makvel@mail.ru'
        ]);

        dump($result);

        $this->assertTrue($result['status'], $result['errorMsg'] ?? '');
        $this->assertNotEmpty($result['data']['mail_id'] ?? null);
    }

    public function test_send_later(): void
    {
        config([
            'database.connections.sqlite.database' => database_path('database.sqlite'),
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $scheduledAt = now()->subSeconds(120)->toDateTimeString();

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
            'to'      => self::TEST_EMAIL,
            'subject' => 'API_Mail emailQueue subject',
            'html'    => '<p>emailQueue test body.</p>',
        ]);
        $this->assertTrue($queued['status'], $queued['errorMsg'] ?? '');

        $result = API_Mail::run('emailQueue', ['email' => self::TEST_EMAIL]);

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
            'to'      => self::TEST_EMAIL,
            'subject' => 'API_Mail deleteEmail subject',
            'html'    => '<p>deleteEmail test body.</p>',
            'scheduledAt' => now()->subSeconds(120)->toDateTimeString()
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
            'to'      => self::TEST_EMAIL,
            'subject' => 'API_Mail deleteQueueByEmail subject',
            'html'    => '<p>deleteQueueByEmail test body.</p>',
        ]);
        $this->assertTrue($queued['status'], $queued['errorMsg'] ?? '');

        $result = API_Mail::run('deleteQueueByEmail', ['email' => self::TEST_EMAIL]);

        $this->assertTrue($result['status'], $result['errorMsg'] ?? '');
        $this->assertGreaterThanOrEqual(1, $result['data']['deleted']);

        $this->assertDatabaseMissing('magicPro_mail_messages', [
            'id' => $queued['data']['id'],
        ]);
    }

    public function test_send_queue(): void
    {
        config([
            'mail.default' => 'smtp',
            'database.connections.sqlite.database' => database_path('database.sqlite'),
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $result = API_Mail::run('sendQueue', []);

        $this->assertTrue($result['status'], $result['errorMsg'] ?? '');
        $this->assertSame(
            $result['data']['total'],
            $result['data']['sent'] + $result['data']['failed']
        );
    }
}
