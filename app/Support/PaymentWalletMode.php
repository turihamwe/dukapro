<?php

namespace App\Support;

use App\Models\Business;

class PaymentWalletMode
{
    public static function businessEnabled(Business $business): bool
    {
        $settings = $business->settings ?? [];

        return (bool) ($settings['payment_wallets_mode'] ?? false);
    }

    public static function active(?Business $business): bool
    {
        if (! $business) {
            return false;
        }

        return self::businessEnabled($business);
    }
}
