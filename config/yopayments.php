<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Yo! Payments (YoPayments) — Uganda mobile money gateway
    |--------------------------------------------------------------------------
    | Credentials are stored in system_settings by SuperAdmin and loaded here
    | for platform subscription collections from business owners.
    */
    'enabled' => env('YOPAYMENTS_ENABLED', false),

    'environment' => env('YOPAYMENTS_ENVIRONMENT', 'sandbox'),

    'sandbox_api_url' => env(
        'YOPAYMENTS_SANDBOX_API_URL',
        'https://sandbox.yo.co.ug/services/yopaymentsdev/task.php'
    ),

    'live_api_url' => env(
        'YOPAYMENTS_LIVE_API_URL',
        'https://paymentsapi1.yo.co.ug/ybs/task.php'
    ),

    'api_username' => env('YOPAYMENTS_API_USERNAME'),

    'api_password' => env('YOPAYMENTS_API_PASSWORD'),

    'account_id' => env('YOPAYMENTS_ACCOUNT_ID'),

    /*
    | SystemSetting keys (preferred when configured in SuperAdmin UI)
    */
    'settings_keys' => [
        'enabled' => 'yopayments_enabled',
        'environment' => 'yopayments_environment',
        'api_username' => 'yopayments_api_username',
        'api_password' => 'yopayments_api_password',
        'account_id' => 'yopayments_account_id',
    ],
];
