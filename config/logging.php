<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */
    'default' => env('LOG_CHANNEL', 'file'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    */
    'channels' => [
        'file' => [
            'driver' => 'file',
            'path' => __DIR__ . '/../storage/logs/isekaiphp.log',
            'permission' => 0644,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => __DIR__ . '/../storage/logs',
            'days' => env('LOG_DAYS', 7),
            'permission' => 0644,
        ],

        'syslog' => [
            'driver' => 'syslog',
            'ident' => 'isekaiphp',
            'facility' => LOG_USER,
        ],
    ],
];

