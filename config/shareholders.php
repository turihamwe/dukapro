<?php

return [
    'total_shares' => 100,
    'price_per_share' => 1000000,
    'max_shareholders' => 100,
    'earnings_cap_multiplier' => 3,
    'subscription_open' => env('SHAREHOLDER_SUBSCRIPTION_OPEN', true),
    'min_shares_per_application' => 0.01,
    'default_promotion_shares' => 1,
];
