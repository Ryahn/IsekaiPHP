<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Session Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default session driver that will be used on
    | requests. By default, we will use the file driver which is simple
    | and works well for most applications.
    |
    */
    'driver' => env('SESSION_DRIVER', 'file'),

    /*
    |--------------------------------------------------------------------------
    | Session Lifetime
    |--------------------------------------------------------------------------
    |
    | Here you may specify the number of minutes that you wish the session
    | to be allowed to remain idle before it expires.
    |
    */
    'lifetime' => env('SESSION_LIFETIME', 120),

    /*
    |--------------------------------------------------------------------------
    | Session Stores
    |--------------------------------------------------------------------------
    |
    | Here you may configure the session stores for your application.
    |
    */
    'stores' => [
        'file' => [
            'driver' => 'file',
            'path' => __DIR__ . '/../storage/sessions',
            'lifetime' => 7200,
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'sessions',
            'lifetime' => 7200,
        ],

        'cookie' => [
            'driver' => 'cookie',
            'name' => 'isekaiphp_session',
            'lifetime' => 7200,
            'path' => '/',
            'domain' => env('SESSION_DOMAIN', null),
            'secure' => env('SESSION_SECURE', false),
            'http_only' => true,
            'encryption_key' => env('APP_KEY', 'change-this-key'),
        ],
    ],
];

