<?php

namespace MagicProSrc\Cleanup;

use MagicProSrc\Api\AbstractApi;

/**
 * Check of the articles: POST /a_dmin/api/cleanup.
 *
 * Two commands and no arguments. The run repairs everything it finds and
 * answers with the name of the report; the list is what lies on the disk.
 */
class API_Cleanup extends AbstractApi
{
    protected array $map = [
        'run'     => 'runCheck',
        'reports' => 'reportList',
    ];

    protected function runCheck(array $params): array
    {
        $result = ArticleCheck::run();

        $result['reports'] = CleanupReport::all();

        return $result;
    }

    protected function reportList(array $params): array
    {
        return ['reports' => CleanupReport::all()];
    }
}
