<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google_maps' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    // Google Places reviews for the homepage. Falls back to the Maps key when a
    // dedicated Places key isn't set (Places API must be enabled on that key).
    // The place_id (Incalake / "Inca Lake", Puno) is public, so it ships as the
    // default; env can still override it.
    'google_places' => [
        'api_key' => env('GOOGLE_PLACES_API_KEY', env('GOOGLE_MAPS_API_KEY')),
        'place_id' => env('GOOGLE_PLACE_ID', 'ChIJKcYdY-tpXZERKudgfrNW0oU'),
    ],

    'culqi' => [
        'public_key' => env('CULQI_PUBLIC_KEY'),
        'secret_key' => env('CULQI_SECRET_KEY'),
    ],

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET', env('PAYPAL_SECRET')),
        'mode' => env('PAYPAL_MODE', 'sandbox'),
    ],

    // Operational addresses — override per environment without code changes.
    'incalake' => [
        'reservations_email' => env('RESERVATIONS_EMAIL', 'reservas@incalake.com'),
        'admin_url' => env('ADMIN_URL', 'https://incalake-admin.vercel.app'),
    ],

    // Public Nuxt site. Its tour pages are cached with Vercel ISR, so a publish
    // is invisible until that cache regenerates. `revalidate_token` is the
    // Vercel bypassToken: a GET carrying it as x-prerender-revalidate purges
    // that path on demand. Leave it empty to disable purging entirely.
    'frontend' => [
        'url' => env('FRONTEND_PUBLIC_URL', env('FRONTEND_URL', 'https://incalake-frontend.vercel.app')),
        'revalidate_token' => env('FRONTEND_REVALIDATE_TOKEN'),
        'locales' => ['es', 'en', 'pt', 'fr', 'de', 'it'],
    ],

];
