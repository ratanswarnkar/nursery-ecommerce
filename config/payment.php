<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | This specifies the default payment gateway driver used for processing
    | customer transactions. For local development and test environments,
    | a safe 'null' gateway is utilized.
    |
    | Intended production gateway: PNB IPG (Phase 6.2-B upon official documentation).
    |
    */

    'default' => env('PAYMENT_GATEWAY', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Supported Currency
    |--------------------------------------------------------------------------
    |
    | Standard transactional currency code. Defaults to Indian Rupee (INR).
    |
    */

    'currency' => env('PAYMENT_CURRENCY', 'INR'),

    /*
    |--------------------------------------------------------------------------
    | Gateway Timeout
    |--------------------------------------------------------------------------
    |
    | Network request timeout for gateway interactions in seconds.
    |
    */

    'timeout' => env('PAYMENT_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Available Gateways
    |--------------------------------------------------------------------------
    |
    | Configuration for all registered payment gateways.
    |
    */

    'gateways' => [
        'null' => [
            'name' => 'Null Development Gateway',
            'driver' => 'null',
        ],

        'razorpay' => [
            'name' => 'Razorpay Standard Checkout',
            'driver' => 'razorpay',
        ],

        // 'pnb' => [
        //     'name' => 'PNB Internet Payment Gateway (Planned Phase 6.2-B)',
        //     'driver' => 'pnb',
        // ],
    ],

];
