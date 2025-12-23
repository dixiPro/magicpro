<?php

return [

    'LANGUAGE' => [
        'label'   => 'language',
        'type'    => 'string',
        'default' => 'ru',
        'mutable' => true,
    ],

    // 'ADMIN_LINK' => [
    //     'label'   => 'admin_link',
    //     'type'    => 'array',
    //     'default' => [
    //         ['Редаткор статей', '/a_dmin/artEditor#1'],
    //         ['Администраторы', '/a_dmin/adminList'],
    //     ],
    //     'mutable' => false,
    // ],

    // 'ADDITIONAL_LINK' => [
    //     'label'   => 'admin_link',
    //     'type'    => 'array',
    //     'default' => [
    //         ['Редаткор статей', '/a_dmin/artEditor#1'],
    //         ['Администраторы', '/a_dmin/adminList'],
    //     ],
    //     'mutable' => true,
    // ],


    'PUBLIC_UPLOAD_DIR' => [
        'label'   => 'public_upload_dir',
        'type'    => 'string',
        'default' => '/design',
        'mutable' => true,
    ],

    'EXCLUDED_ROUTES' => [
        'label'   => 'excluded_routes',
        'type'    => 'array',
        'default' => [
            'livewire',
            'telescope',
            'horizon',
            'nova',
            'debugbar',
            'admin',
            'public',
            'f_ilament',
            'storage'
        ],
        'mutable' => true,
    ],

    'RENDER_URL' => [
        'label'   => 'render_url',
        'type'    => 'array',
        'default' => [
            '/robots.txt',
            '/sitemap.xml'
        ],
        'mutable' => true,
    ],


    'ENABLE_URL_PARAMS' => [
        'label'   => 'enable_url_params',
        'type'    => 'array',
        'default' => [
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_term',
            'utm_content',
            'gclid',     // Google Ads
            'fbclid',    // Facebook / Instagram
            'yclid',     // Яндекс.Директ
            'ttclid',    // TikTok Ads
            'msclkid',   // Microsoft Ads (Bing)
            '_openstat',
        ],
        'mutable' => true,
    ],

    'STATIC_HTML_DIR' => [
        'label'   => 'html_pages_dir',
        'type'    => 'localpath',
        'default' => '/public/html',
        'mutable' => true,
    ],

];
