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

    'packlink' => [
        'api_key' => env('PACKLINK_API_KEY'),
        'base_url' => env('PACKLINK_BASE_URL', 'https://api.packlink.com/v1'),
        'origin' => [
            'country' => env('PACKLINK_ORIGIN_COUNTRY', 'FR'),
            'zip' => env('PACKLINK_ORIGIN_ZIP', ''),
            'city' => env('PACKLINK_ORIGIN_CITY', ''),
            'height' => env('PACKLINK_DEFAULT_HEIGHT', 10),
            'width' => env('PACKLINK_DEFAULT_WIDTH', 10),
            'length' => env('PACKLINK_DEFAULT_LENGTH', 10),
        ],
        'env' => env('PACKLINK_ENV', 'production'),
    ],

    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
        'key' => env('STRIPE_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'secret' => env('PAYPAL_SECRET'),
        'mode' => env('PAYPAL_MODE', 'live'), // live ou sandbox
        'return_url' => env('PAYPAL_RETURN_URL'),
        'cancel_url' => env('PAYPAL_CANCEL_URL'),
        'hosted_button_id' => env('PAYPAL_HOSTED_BUTTON_ID'),
    ],

    'boxtal' => [
        'base_url' => env('BOXTAL_BASE_URL', 'https://test.envoimoinscher.com/api/v1'),
        'key' => env('BOXTAL_KEY'),
        'secret' => env('BOXTAL_SECRET'),
        'login' => env('BOXTAL_LOGIN'),
        'password' => env('BOXTAL_PASSWORD'),
        'from' => [
            'country' => env('BOXTAL_FROM_COUNTRY', 'FR'),
            'zip' => env('BOXTAL_FROM_ZIP'),
            'city' => env('BOXTAL_FROM_CITY'),
            'type' => env('BOXTAL_FROM_TYPE', 'entreprise'), // entreprise | particulier
        ],
        'defaults' => [
            'length' => env('BOXTAL_DEFAULT_LENGTH', 20),
            'width' => env('BOXTAL_DEFAULT_WIDTH', 20),
            'height' => env('BOXTAL_DEFAULT_HEIGHT', 20),
            'content_code' => env('BOXTAL_CONTENT_CODE', '10150'), // code_contenu requis
        ],
    ],

];
