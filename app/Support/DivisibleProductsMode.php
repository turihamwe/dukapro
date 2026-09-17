<?php

namespace App\Support;

use App\Models\Business;
use App\Models\SystemSetting;
use Illuminate\Validation\ValidationException;

class DivisibleProductsMode
{
    /**
     * Level 1 — platform-wide master switch (default ON).
     */
    public static function platformEnabled(): bool
    {
        return (bool) (int) SystemSetting::get('divisible_products_enabled', '1');
    }

    /**
     * Level 2 — business owner enabled fractional quantities for their store.
     */
    public static function businessEnabled(Business $business): bool
    {
        if (! self::platformEnabled()) {
            return false;
        }

        return (bool) $business->divisible_products_enabled;
    }

    public static function active(Business $business): bool
    {
        return self::businessEnabled($business);
    }

    public static function quantityValidationRules(Business $business): array
    {
        if (self::active($business)) {
            return ['required', 'numeric', 'min:0.001'];
        }

        return ['required', 'integer', 'min:1'];
    }

    public static function assertWholeQuantity(Business $business, float $quantity, ?string $productLabel = null): void
    {
        if (self::active($business)) {
            return;
        }

        if (abs($quantity - round($quantity)) > 0.0001) {
            $label = $productLabel ? " for {$productLabel}" : '';

            throw ValidationException::withMessages([
                'items' => 'Whole-number quantities only' . $label . '. Enable divisible products in Business Profile to sell fractions.',
            ]);
        }
    }
}
