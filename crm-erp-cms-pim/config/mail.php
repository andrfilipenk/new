<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mail Driver
    |--------------------------------------------------------------------------
    |
    | Supported: "smtp", "sendmail", "mail"
    |
    */
    'driver' => env('MAIL_DRIVER', 'mail'),

    /*
    |--------------------------------------------------------------------------
    | SMTP Configuration
    |--------------------------------------------------------------------------
    */
    'smtp' => [
        'host' => env('MAIL_HOST', 'smtp.mailtrap.io'),
        'port' => env('MAIL_PORT', 587),
        'username' => env('MAIL_USERNAME'),
        'password' => env('MAIL_PASSWORD'),
        'encryption' => env('MAIL_ENCRYPTION', 'tls'), // tls, ssl, or null
    ],

    /*
    |--------------------------------------------------------------------------
    | Sendmail Configuration
    |--------------------------------------------------------------------------
    */
    'sendmail' => [
        'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -t -i'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    */
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
        'name' => env('MAIL_FROM_NAME', 'CRM-ERP Platform'),
    ],
];
