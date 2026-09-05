<?php

namespace App\Modules;

/**
 * Canonical module identifiers for tenant capabilities.
 */
class ModuleKeys
{
    public const RESTAURANT = 'restaurant';

    public const BAR_SHIFT = 'bar_shift';

    public const CATALOG_VARIANTS = 'catalog_variants';

    public static function all(): array
    {
        return [
            self::RESTAURANT,
            self::BAR_SHIFT,
            self::CATALOG_VARIANTS,
        ];
    }
}
