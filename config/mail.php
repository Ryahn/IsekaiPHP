<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Mail Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default mail driver that will be used to
    | send emails. You may set this to any of the drivers defined in the
    | "drivers" array below.
    |
    */
    'default' => env('MAIL_DRIVER', 'smtp'),

    /*
    |--------------------------------------------------------------------------
    | Mail Drivers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the mail drivers for your application.
    |
    */
    'drivers' => [
        'smtp' => [
            'driver' => 'smtp',
            'host' => env('MAIL_HOST', 'smtp.mailtrap.io'),
            'port' => env('MAIL_PORT', 2525),
            'from' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
            'from_name' => env('MAIL_FROM_NAME', 'IsekaiPHP'),
        ],

        'mailgun' => [
            'driver' => 'mailgun',
            'domain' => env('MAILGUN_DOMAIN'),
            'secret' => env('MAILGUN_SECRET'),
            'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        ],

        'sendgrid' => [
            'driver' => 'sendgrid',
            'api_key' => env('SENDGRID_API_KEY'),
        ],
    ],
];

