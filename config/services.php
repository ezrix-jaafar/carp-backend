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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    
    'toyyibpay' => [
        'env' => env('TOYYIBPAY_ENV', 'sandbox'),
        'sandbox' => [
            'api_url' => env('TOYYIBPAY_SANDBOX_API_URL', 'https://dev.toyyibpay.com/index.php/api/'),
            'payment_url' => env('TOYYIBPAY_SANDBOX_PAYMENT_URL', 'https://dev.toyyibpay.com/'),
            'secret_key' => env('TOYYIBPAY_SANDBOX_SECRET_KEY'),
            'category_code' => env('TOYYIBPAY_SANDBOX_CATEGORY_CODE'),
        ],
        'production' => [
            'api_url' => env('TOYYIBPAY_PRODUCTION_API_URL', 'https://toyyibpay.com/index.php/api/'),
            'payment_url' => env('TOYYIBPAY_PRODUCTION_PAYMENT_URL', 'https://toyyibpay.com/'),
            'secret_key' => env('TOYYIBPAY_PRODUCTION_SECRET_KEY'),
            'category_code' => env('TOYYIBPAY_PRODUCTION_CATEGORY_CODE'),
        ],
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

];
