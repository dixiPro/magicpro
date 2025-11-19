<?php

return [

    'STATIC_HTML_DIR' => [
        'label'   => 'Каталог HTML-кеша',
        'type'    => 'localpath',
        'default' => 'public/html', // от корня проекта
        'mutable' => false,
    ],

    'STATIC_HTML_CREATE_DIR' => [
        'label'   => 'Каталог генерации HTML-кеша',
        'type'    => 'localpath', // без слеша в на конце
        'default' => 'storage/app/private/magic/html', // от корня проекта
        'mutable' => true,
    ],

    'STATIC_HTML_ENABLE' => [
        'label'   => 'Статический HTML-кеш',
        'type'    => 'boolean', // без слеша в на конце
        'default' => true,
        'mutable' => false,
    ],

    'HOST_DEV' => [
        'label'   => 'Сервер разработки',
        'type'    => 'string', // без слеша в на конце
        'default' => 'mpro2.test',
        'mutable' => true,
    ],

    'EXCLUDED_ROUTES' => [
        'label'   => 'Страницы исключенные из динамического раута',
        'type'    => 'array', // без слеша в на конце
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
];
