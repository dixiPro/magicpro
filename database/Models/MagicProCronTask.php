<?php

namespace MagicProDatabaseModels; // в композере прописывается

use Illuminate\Database\Eloquent\Model;

/**
 * One scheduled task created from the admin panel.
 *
 * `controller` holds two things in one string, `article|method`. The left part
 * is the name of an article, not a class name: the runner builds
 * \MagicProControllers\{article} out of it. The right part is a public method
 * of that controller, and the runner calls it straight — no request, no view.
 * There is no separate column for the method on purpose: one field, one thing
 * to fill in.
 *
 * `params` reach the method as a single array.
 *
 * There is no status of the last run on purpose. Cron cannot do anything with
 * it: there are no retries and a task never switches itself off. Whatever
 * happens inside the controller is the controller's own business.
 * `last_run_at` is stamped before the call and means only that the scheduler
 * reached this task.
 */
class MagicProCronTask extends Model
{
    protected $table = 'magicPro_cron_tasks';

    protected $fillable = [
        'name',
        'controller',
        'params',
        'cron',
        'enabled',
        'last_run_at',
    ];

    protected $casts = [
        'params'      => 'array',
        'enabled'     => 'boolean',
        'last_run_at' => 'datetime',
    ];

    protected $attributes = [
        'enabled' => true,
    ];
}
