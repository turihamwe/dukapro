<?php

namespace App\Support;

use App\Models\Business;
use App\Models\SystemSetting;

class SupplierCreditMode
{
    public static function platformEnabled(): bool
    {
        return (bool) (int) SystemSetting::get('supplier_credit_platform_enabled', 0);
    }

    public static function businessEnabled(Business $business): bool
    {
        if (! self::platformEnabled()) {
            return false;
        }

        $settings = $business->settings ?? [];

        return (bool) ($settings['supplier_credit_mode'] ?? false);
    }

    public static function active(?Business $business): bool
    {
        if (! $business) {
            return false;
        }

        return self::businessEnabled($business);
    }
}
