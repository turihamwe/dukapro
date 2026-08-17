<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Local Mobile Money Provider
    |--------------------------------------------------------------------------
    |
    | Configure the local M-Pesa / mobile money integration. In production,
    | replace the simulated STK push with your provider's API credentials.
    |
    */

    'provider' => env('MOBILE_MONEY_PROVIDER', 'local_mpesa'),

    'webhook_secret' => env('MOBILE_MONEY_WEBHOOK_SECRET'),

    'stk_push_url' => env('MOBILE_MONEY_STK_URL'),

    'shortcode' => env('MOBILE_MONEY_SHORTCODE'),

    'passkey' => env('MOBILE_MONEY_PASSKEY'),

    'consumer_key' => env('MOBILE_MONEY_CONSUMER_KEY'),

    'consumer_secret' => env('MOBILE_MONEY_CONSUMER_SECRET'),

];
