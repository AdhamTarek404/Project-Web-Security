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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // === Stripe Connect (split payments) =====================================
    // Description: "Stripe Connect for split payments between platform,
    // restaurant, and rider."
    'stripe' => [
        'secret'              => env('STRIPE_SECRET'),
        'publishable'         => env('STRIPE_PUBLISHABLE'),
        'webhook_secret'      => env('STRIPE_WEBHOOK_SECRET'),
        'currency'            => env('STRIPE_CURRENCY', 'usd'),
        // For server-side demo confirmation (Stripe test tokens).
        'test_payment_method' => env('STRIPE_TEST_PAYMENT_METHOD', 'pm_card_visa'),
    ],

    'payment' => [
        // 'stripe' to use real Stripe Connect, 'fake' for dev/test logging gateway.
        'driver' => env('PAYMENT_DRIVER', 'fake'),
    ],

    // === Google Maps Distance Matrix API =====================================
    // Description: "Google Maps Distance Matrix API for rider-to-restaurant
    // distance calculation."
    'google_maps' => [
        'key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    'distance' => [
        // 'google' to call the real Distance Matrix API, 'haversine' for offline.
        'driver' => env('DISTANCE_DRIVER', 'haversine'),
    ],

];
