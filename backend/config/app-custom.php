<?php

return [
    'cache' => [
        'default' => env('CACHE_DRIVER', 'file'),
    ],

    'queue' => [
        'default' => env('QUEUE_CONNECTION', 'database'),
    ],

    'mail' => [
        'mailers' => [
            'smtp' => [
                'transport' => 'smtp',
                'host' => env('MAIL_HOST', 'smtp.mailgun.org'),
                'port' => env('MAIL_PORT', 587),
                'encryption' => env('MAIL_ENCRYPTION', 'tls'),
                'username' => env('MAIL_USERNAME'),
                'password' => env('MAIL_PASSWORD'),
                'timeout' => null,
                'auth_mode' => null,
            ],
        ],

        'from' => [
            'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
            'name' => env('MAIL_FROM_NAME', 'Incalake'),
        ],
    ],

    'logging' => [
        'channels' => [
            'daily' => [
                'driver' => 'daily',
                'path' => storage_path('logs/laravel.log'),
                'level' => env('LOG_LEVEL', 'debug'),
                'days' => 14,
            ],
        ],
    ],

    'services' => [
        'sentry' => [
            'dsn' => env('SENTRY_LARAVEL_DSN'),
        ],
    ],
];