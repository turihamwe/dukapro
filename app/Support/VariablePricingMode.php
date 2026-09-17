<?php

namespace App\Support;

use App\Models\Business;
use App\Models\SystemSetting;

class VariablePricingMode
{
    /**
     * Level 1 — platform-wide master switch (default ON).
     */
    public static function platformEnabled(): bool
    {
        return (bool) (int) SystemSetting::get('variable_pricing_enabled', '1');
    }

    /**
     * Level 2 — business owner enabled variable pricing for their store.
     */
    public static function businessEnabled(Business $business): bool
    {
        if (! self::platformEnabled()) {
            return false;
        }

        return (bool) $business->variable_pricing_enabled;
    }

    public static function active(Business $business): bool
    {
        return self::businessEnabled($business);
    }
}
