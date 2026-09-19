<?php

return [

    'LANGUAGE' => [
        'label'   => 'language',
        'type'    => 'list',
        'values'  => ['ru', 'en'],
        'default' => 'en',
        'mutable' => true,
    ],

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
            'utm_id',
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

    // писать ли в storage/logs/cron.log строку об отработавшей задаче. Беды
    // пишутся всегда, а строка выполнения нужна, пока присматриваешься
    'CRON_LOG_SUCCESS' => [
        'label'   => 'cron_log_success',
        'type'    => 'boolean',
        'default' => true,
        'mutable' => true,
    ],


    'ARCHIVE_DAYS' => [
        'label'   => 'archive_days',
        'type'    => 'integer',
        'min'     => 0,
        'max'     => 3650,
        'default' => 30,
        'mutable' => true,
    ],

    'RESIZE' => [
        'label'   => 'resize_params',
        'type'    => 'group',
        'data'    => [

            'MAX_RESIZE' => [
                'label'   => 'image_max_size',
                'type'    => 'integer',
                'min'     => 10,
                'max'     => 10000,
                'default' => 10000,
                'mutable' => true,
            ],

            'RESIZE_FORMAT' => [
                'label'   => 'image_resize_format',
                'type'    => 'list',
                'values'  => ['webp', 'avif', 'jpg', 'png'],
                'default' => 'webp',
                'mutable' => true,
            ],

            // чем кроппер отправляет обрезанный кусок на сервер. Кодирует в
            // рабочий формат всегда сервер, браузер только доставляет пиксели
            'UPLOAD_FORMAT' => [
                'label'   => 'image_upload_format',
                'type'    => 'list',
                'values'  => ['png', 'jpeg'],
                'default' => 'png',
                'mutable' => true,
            ],

            'WEBP_DEF_QUALITY' => [
                'label'   => 'default_webp_quality',
                'type'    => 'integer',
                'min'     => 10,
                'max'     => 100,
                'default' => 82,
                'mutable' => true,
            ],

            'AVIF_DEF_QUALITY' => [
                'label'   => 'default_avif_quality',
                'type'    => 'integer',
                'min'     => 10,
                'max'     => 100,
                'default' => 50,
                'mutable' => true,
            ],

            'JPG_DEF_QUALITY' => [
                'label'   => 'default_jpg_quality',
                'type'    => 'integer',
                'min'     => 10,
                'max'     => 100,
                'default' => 70,
                'mutable' => true,
            ],

            // у png качества нет, есть уровень сжатия: 0 быстро и крупно,
            // 9 медленно и мелко, картинка при этом одна и та же
            'PNG_COMPRESSION' => [
                'label'   => 'png_compression',
                'type'    => 'integer',
                'min'     => 0,
                'max'     => 9,
                'default' => 6,
                'mutable' => true,
            ],

            'IMAGE_ERROR' => [
                'label'   => 'image_error',
                'type'    => 'string',
                'default' => '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="600" viewBox="0 0 800 600"><rect width="100%" height="100%" fill="#eee"/> <text x="50%" y="50%" text-anchor="middle" dominant-baseline="middle"> Image not found </text></svg>',
                'mutable' => true,
            ],
        ],
    ],

    // регистрация и вход на сайте (API_SiteAuth). Правится на странице
    // «Пользователи», а не в «Настройках»: `page` уводит группу туда
    'AUTH' => [
        'label'   => 'auth_params',
        'type'    => 'group',
        'page'    => 'users',
        'data'    => [
            'authUrl' => [
                'label'   => 'auth_url',
                'type'    => 'string',
                'default' => '/auth2',
                'mutable' => true,
            ],
            'registerUrl' => [
                'label'   => 'auth_register_url',
                'type'    => 'string',
                'default' => '/auth2_register',
                'mutable' => true,
            ],
            'resetPasswordUrl' => [
                'label'   => 'auth_reset_password_url',
                'type'    => 'string',
                'default' => '/auth2_reset',
                'mutable' => true,
            ],
            'postUrl' => [
                'label'   => 'auth_post_url',
                'type'    => 'string',
                'default' => '/',
                'mutable' => true,
            ],
            'authLetter' => [
                'label'   => 'auth_letter',
                'type'    => 'string',
                'default' => 'magic::auth2_letter_register',
                'mutable' => true,
            ],
            'postLetter' => [
                'label'   => 'auth_post_letter',
                'type'    => 'string',
                'default' => 'magic::auth2_letter_post',
                'mutable' => true,
            ],
            'resetPasswordLetter' => [
                'label'   => 'auth_reset_password_letter',
                'type'    => 'string',
                'default' => 'magic::auth2_letter_reset',
                'mutable' => true,
            ],
            'emailTokenMinutes' => [
                'label'   => 'auth_email_token_minutes',
                'type'    => 'integer',
                'min'     => 1,
                'max'     => 525600,
                'default' => 30,
                'mutable' => true,
            ],
            'registerTokenMinutes' => [
                'label'   => 'auth_register_token_minutes',
                'type'    => 'integer',
                'min'     => 1,
                'max'     => 525600,
                'default' => 1440,
                'mutable' => true,
            ],
            'resetPasswordTokenMinutes' => [
                'label'   => 'auth_reset_password_token_minutes',
                'type'    => 'integer',
                'min'     => 1,
                'max'     => 525600,
                'default' => 120,
                'mutable' => true,
            ],
        ],
    ],
];
