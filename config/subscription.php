<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Platform subscription billing plans
    |--------------------------------------------------------------------------
    |
    | Amounts are in UGX. Days control how long access is extended after payment.
    |
    */
    'default_plan' => env('SUBSCRIPTION_DEFAULT_PLAN', 'monthly'),

    'plans' => [
        'monthly' => [
            'key' => 'monthly',
            'label' => '1 Month',
            'description' => 'Monthly access to DukaPro',
            'amount' => (float) env('SUBSCRIPTION_MONTHLY_AMOUNT', 100000),
            'days' => (int) env('SUBSCRIPTION_MONTHLY_DAYS', 30),
        ],
        'yearly' => [
            'key' => 'yearly',
            'label' => '1 Year',
            'description' => 'Annual access — best value',
            'amount' => (float) env('SUBSCRIPTION_YEARLY_AMOUNT', 1000000),
            'days' => (int) env('SUBSCRIPTION_YEARLY_DAYS', 365),
        ],
    ],

];
