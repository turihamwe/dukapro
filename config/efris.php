<?php

return [
    /*
    | System-wide master switch. When false, EFRIS is dormant everywhere.
    | Superadmins can override via System Settings ("Use EFRIS").
    */
    'use_efris' => env('USE_EFRIS', false),

    'base_url' => env('EFRIS_BASE_URL', 'https://weafcompany.com'),

    'default_environment' => env('EFRIS_DEFAULT_ENVIRONMENT', 'sandbox'),

    'environments' => [
        'sandbox' => 'Sandbox',
        'production' => 'Production',
    ],

    'payment_modes' => [
        'cash' => '101',
        'mobile_money' => '102',
        'bank' => '103',
        'credit' => '101',
    ],

    'default_buyer' => [
        'tin' => env('EFRIS_DEFAULT_BUYER_TIN', '999999999'),
        'business_name' => 'Walk-in Customer',
        'legal_name' => 'Walk-in Customer',
        'type' => '1',
        'address' => 'N/A',
        'email' => 'walkin@example.com',
        'line_phone' => '0700000000',
        'mobile_phone' => '0700000000',
    ],

    'unit_map' => [
        'piece' => 'PCE',
        'pcs' => 'PCE',
        'kg' => 'KGM',
        'kilogram' => 'KGM',
        'litre' => 'LTR',
        'liter' => 'LTR',
        'box' => 'BX',
        'pack' => 'PK',
    ],

    'default_unit' => 'PCE',

    /*
    | URA / EFRIS line classification: 1 = goods, 2 = services (WEAF fiscal receipt).
    */
    'item_type' => [
        'goods' => env('EFRIS_ITEM_TYPE_GOODS', '1'),
        'service' => env('EFRIS_ITEM_TYPE_SERVICE', '2'),
    ],

    'service_unit' => env('EFRIS_SERVICE_UNIT', 'SV'),

    'default_service_item_code' => env('EFRIS_DEFAULT_SERVICE_ITEM_CODE', ''),

    'job' => [
        'tries' => 3,
        'backoff_seconds' => [60, 300, 900],
    ],

    'registration' => [
        'product_interest' => env('EFRIS_WEAF_PRODUCT_INTEREST', 'efris_api'),
    ],
];
