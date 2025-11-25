<?php

return [

    'STATIC_HTML_DIR' => [
        'label'   => 'Каталог HTML-кеша от корня проекта',
        'type'    => 'localpath',
        'default' => 'public/html', // 
        'mutable' => true,
    ],

    'STATIC_HTML_CREATE_DIR' => [
        'label'   => 'Каталог генерации HTML-кеша, от корня проекта, без слеша в на конце',
        'type'    => 'localpath', // 
        'default' => 'storage/app/private/magic/html', // от корня проекта
        'mutable' => true,
    ],

    'FILES_JS_UPLOAD' => [
        'label'   => 'Папка для загрузки изображений относительно public, без слеша в на конце',
        'type'    => 'string', // без слеша в на конце
        'default' => 'design',
        'mutable' => true,
    ],


    'EXCLUDED_ROUTES' => [
        'label'   => 'Страницы исключенные из динамического раута, ез слеша в на конце',
        'type'    => 'array', // б
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
        'label'   => 'Дополнительные страницы для рендера в кеш',
        'type'    => 'array', // без слеша в на конце
        'default' => [
            '/robots.txt',
            '/sitemap.xml'
        ],
        'mutable' => true,
    ],
];
