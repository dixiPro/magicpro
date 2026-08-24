<?php

namespace MagicProDatabaseModels; // в композере прописывается

use Illuminate\Database\Eloquent\Model;

/**
 * One scheduled task created from the admin panel.
 *
 * `controller` holds the name of an article, not a class name: the runner
 * builds \MagicProControllers\{controller} out of it, the same way
 * DynamicRouteHandler does for a URL call. The article must be a route, must
 * use a controller and must be adminOnly with postEnable — CronTaskChecker
 * keeps that list.
 *
 * The task is always called with POST, so `params` land in postParams.
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
