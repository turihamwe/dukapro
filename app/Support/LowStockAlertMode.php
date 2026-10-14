<?php

namespace App\Support;

use App\Models\Business;

class LowStockAlertMode
{
    public const COMBINED = 'combined';

    public const PER_VARIANT = 'per_variant';

    public static function all(): array
    {
        return [
            self::COMBINED,
            self::PER_VARIANT,
        ];
    }

    public static function labels(): array
    {
        return [
            self::COMBINED => 'Whole product (sum all variants)',
            self::PER_VARIANT => 'Per variant (each size/color separately)',
        ];
    }

    public static function forBusiness(Business $business): string
    {
        if (! $business->usesProductVariants()) {
            return self::COMBINED;
        }

        $mode = $business->settings['low_stock_alert_mode'] ?? self::COMBINED;

        return in_array($mode, self::all(), true) ? $mode : self::COMBINED;
    }

    public static function isPerVariant(Business $business): bool
    {
        return self::forBusiness($business) === self::PER_VARIANT;
    }
}
