<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('magicPro_cron_tasks', function (Blueprint $table) {
            $table->id();

            // human readable name, shown in the admin list only
            $table->string('name');

            // article|method: the class is \MagicProControllers\{article},
            // the method is the public one the task calls
            $table->string('controller');

            // call parameters, always sent as POST; may be empty
            $table->json('params')->nullable();

            // cron expression, five fields, server timezone
            $table->string('cron');

            $table->boolean('enabled')->default(true);

            // stamped right before the controller is called: the mark says the
            // scheduler got this far, not that the controller succeeded
            $table->timestamp('last_run_at')->nullable();

            $table->timestamps();

            // the scheduler reads the enabled tasks on every run, once a minute
            $table->index('enabled');
        });

        echo "Table magicPro_cron_tasks was created:\n";
    }

    public function down(): void
    {
        Schema::dropIfExists('magicPro_cron_tasks');
    }
};
