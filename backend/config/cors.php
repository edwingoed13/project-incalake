<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    // CORS is now enabled in production too, so localhost dev (admin/frontend)
    // can talk to api.incalake.com. With credentials=true we cannot use the
    // wildcard origin -- every allowed origin must be enumerated.
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        // Local dev
        'http://localhost:3000',  // admin
        'http://localhost:3001',  // frontend
        'http://localhost:8001',  // backend (Sanctum csrf-cookie)
        // Production
        'https://incalake.com',
        'https://www.incalake.com',
        'https://admin.incalake.com',
    ],

    // Vercel preview / branch deploys — ONLY this project's deployments.
    // A bare `.*\.vercel\.app` pattern with supports_credentials=true would let
    // ANY Vercel-hosted site (anyone can deploy one) make credentialed
    // cross-origin requests against this API.
    'allowed_origins_patterns' => [
        '#^https://incalake-frontend[a-z0-9-]*\.vercel\.app$#',
        '#^https://incalake-admin[a-z0-9-]*\.vercel\.app$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Required for Sanctum SPA auth (cookies must travel cross-origin).
    'supports_credentials' => true,

];
