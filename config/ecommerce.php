<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Delivery & Shipping Configuration
    |--------------------------------------------------------------------------
    |
    | Flat rate delivery fee applied to orders at or below ₹1,000 within Delhi NCR.
    | Exact commercial fee remains pending final business decision from
    | Sugandha Farms management. Defaults safely to 0.00 without inventing fees.
    |
    */
    'shipping' => [
        'flat_rate' => env('ECOMMERCE_SHIPPING_FLAT_RATE', '0.00'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Order Lifecycle Reliability Configuration
    |--------------------------------------------------------------------------
    |
    | Threshold in minutes after which an unpaid order in pending status is
    | considered abandoned and eligible for automated cancellation and stock release.
    | Defaults to null unless explicitly configured via UNPAID_ORDER_EXPIRY_MINUTES.
    | When null, the expiration command requires explicit --minutes option.
    |
    */
    'orders' => [
        'unpaid_expiry_minutes' => env('UNPAID_ORDER_EXPIRY_MINUTES') !== null
            ? (int) env('UNPAID_ORDER_EXPIRY_MINUTES')
            : null,
    ],

];
