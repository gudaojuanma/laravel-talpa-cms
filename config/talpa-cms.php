<?php

return [

    'key' => env('TALPA_CMS_KEY', null),

    'secret' => env('TALPA_CMS_SECRET', null),

    'host' => env('TALPA_CMS_HOST', null),

    // 缓存时长（分钟）
    'ttl' => env('TALPA_CMS_TTL', 20),

    'url-header-key' => env('TALPA_CMS_URL_HEADER_KEY', 'TALPA-CMS-URL'),

    'model' => \App\User::class
];