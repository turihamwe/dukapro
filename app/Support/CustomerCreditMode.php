<?php

namespace App\Support;

use App\Models\Business;

class CustomerCreditMode
{
    public static function businessEnabled(Business $business): bool
    {
        $settings = $business->settings ?? [];

        return (bool) ($settings['customer_credit_mode'] ?? false);
    }

    public static function active(?Business $business): bool
    {
        if (! $business) {
            return false;
        }

        return self::businessEnabled($business);
    }
}
