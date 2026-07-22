<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('magicPro_mail_messages', function (Blueprint $table) {
            $table->id();

            // Message-ID почтового провайдера (SES), матч вебхука / deleteEmail
            $table->string('provider_message_id')->nullable()->unique();

            // собственный идентификатор письма (X-SES-MESSAGE-TAGS mail_id=...)
            $table->string('mail_id')->nullable()->unique();

            $table->string('from_email');
            $table->string('to_email');
            $table->string('subject');

            // отрендеренное тело письма
            $table->longText('html');

            // письмо целиком вместе с заголовками, что уходит провайдеру
            $table->longText('raw_message');

            // планируемая / фактическая дата отправки
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();

            // текущий статус письма (queued, sent, delivered, ...)
            $table->string('status');

            // ошибки доставки и обработки, накапливаются массивом
            $table->json('errors')->nullable();

            // сколько попыток отправки было (по нему считается ретрай)
            $table->integer('attempts')->default(0);

            $table->timestamps();

            // правило повтора (to_email + subject) и очередь по адресу
            $table->index(['to_email', 'subject']);

            // выборка кроном + фильтр админки
            $table->index('status');

            // крон: пора отправлять
            $table->index('scheduled_at');
        });

        echo "Table magicPro_mail_messages was created:\n";
    }

    public function down(): void
    {
        Schema::dropIfExists('magicPro_mail_messages');
    }
};
