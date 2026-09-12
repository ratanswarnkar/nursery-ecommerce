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

    'razorpay' => [
        'key_id' => env('RAZORPAY_KEY_ID'),
        'key_secret' => env('RAZORPAY_KEY_SECRET'),
    ],

    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
        'whatsapp' => [
            'api_url' => env('WHATSAPP_OTP_API_URL', 'https://whatsapp.myapi.in.net/send-message'),
            'api_key' => env('WHATSAPP_OTP_API_KEY'),
            'sender' => env('WHATSAPP_OTP_SENDER', '919811114365'),
            'footer' => env('WHATSAPP_OTP_FOOTER'),
            'timeout' => (int) env('WHATSAPP_OTP_TIMEOUT', 10),
            'connect_timeout' => (int) env('WHATSAPP_OTP_CONNECT_TIMEOUT', 5),
        ],
    ],

    'whatsapp' => [
        'api_url' => env('WHATSAPP_OTP_API_URL', 'https://whatsapp.myapi.in.net/send-message'),
        'api_key' => env('WHATSAPP_OTP_API_KEY'),
        'sender' => env('WHATSAPP_OTP_SENDER', '919811114365'),
        'footer' => env('WHATSAPP_OTP_FOOTER'),
        'timeout' => (int) env('WHATSAPP_OTP_TIMEOUT', 10),
        'connect_timeout' => (int) env('WHATSAPP_OTP_CONNECT_TIMEOUT', 5),
    ],
];
