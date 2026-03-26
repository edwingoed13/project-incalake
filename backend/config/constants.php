<?php

return [
    'tour' => [
        'code_prefix' => [
            'ES' => 3,
            'EN' => 3,
            'FR' => 3,
            'DE' => 3,
            'PT' => 3,
            'IT' => 3,
        ],
        'max_images' => 20,
        'allowed_image_types' => ['jpg', 'jpeg', 'png', 'webp'],
        'max_image_size' => 5242880,
        'default_status' => 'draft',
        'booking_anticipation' => 24,
    ],

    'booking' => [
        'code_prefix' => 'BK',
        'code_date_format' => 'Ymd',
        'code_random_length' => 5,
    ],

    'cache' => [
        'tour' => [
            'default_ttl' => 86400,
            'tags_prefix' => 'tour',
        ],
        'categories' => [
            'default_ttl' => 172800,
            'tags_prefix' => 'categories',
        ],
    ],

    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 100,
    ],

    'api' => [
        'rate_limits' => [
            'public' => '60,1',
            'authenticated' => '120,1',
            'admin' => '30,1',
        ],
    ],
];