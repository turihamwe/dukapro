<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Platform billing mode
    |--------------------------------------------------------------------------
    |
    | unified — flat subscription (100k/month); modules are free toggles.
    | addons  — base subscription + per-module monthly add-on fees.
    |
    | Runtime value comes from SystemSetting (superadmin). This is the default.
    |
    */
    'default_mode' => env('BILLING_MODE', 'unified'),

    /*
    |--------------------------------------------------------------------------
    | Default monthly add-on prices (UGX)
    |--------------------------------------------------------------------------
    |
    | Used when superadmin has not set module_prices in platform settings.
    |
    */
    'default_module_prices' => [
        'restaurant' => 20000,
        'bar_shift' => 15000,
        'catalog_variants' => 10000,
    ],

];
