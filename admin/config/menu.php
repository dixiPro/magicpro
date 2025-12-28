<?php

use Illuminate\Support\Facades\Http;
use MagicProSrc\MagicLang;

return [
    [
        'route' => '/',
        'title' => MagicLang::getMsg($value['site']),
        'controller' => '',
        'method' => '',
        'balde' => '',
        'routeName' => 'magic.exportArticle'
    ],
    [
        'route' => '/a_dmin',
        'title' => MagicLang::getMsg($value['title']),
        'controller' => '',
        'method' => '',
        'balde' => '',
    ],

    ['route' => 'admin.title', 'key' => 'title'],
    ['route' => 'admin.start', 'key' => 'start'],

];
