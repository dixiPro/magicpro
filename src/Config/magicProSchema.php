<?php

return [

  'STATIC_HTML_DIR' => [
    'label'   => 'Каталог HTML-кеша от корня проекта',
    'type'    => 'localpath',
    'default' => '/public/html'
  ],

  'STATIC_HTML_CREATE_DIR' => [
    'label'   => 'Каталог генерации HTML-кеша, от корня проекта, без слеша в на конце',
    'type'    => 'localpath', // 
    'default' => '/storage/app/private/magic/html', // от корня проекта
  ],

  'FILES_JS_UPLOAD' => [
    'label'   => 'Папка для загрузки изображений относительно public, без слеша в на конце',
    'type'    => 'string', // без слеша в на конце
    'default' => '/design',
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
    ]
  ],
  'RENDER_URL' => [
    'label'   => 'Дополнительные страницы для рендера в кеш',
    'type'    => 'array', // без слеша в на конце
    'default' => [
      '/robots.txt',
      '/sitemap.xml'
    ]
  ],

  'ENABLE_URL_PARAMS' => [
    'label'   => 'Разрешенные url параметры',
    'type'    => 'array', // без слеша в на конце
    'default' => [
      // Стандартные UTM
      'utm_source',
      'utm_medium',
      'utm_campaign',
      'utm_term',
      'utm_content',

      // Рекламные идентификаторы
      'gclid',     // Google Ads
      'fbclid',    // Facebook / Instagram
      'yclid',     // Яндекс.Директ
      'ttclid',    // TikTok Ads
      'msclkid',   // Microsoft Ads (Bing)

      // Альтернативные трекинги
      '_openstat', // Яндекс, Mail.ru
      // 'aff_id',    // Партнёрские ID
      // 'ref',
      // 'partner_id',
      // 'click_id',
      // 'cid',
      // 'track_id',
    ]
  ],
];
