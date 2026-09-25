<?php

namespace App\Enums;

class BusinessOperatingMode
{
    public const RETAIL = 'retail';

    public const SERVICE_BASED = 'service_based';

    public const RENTAL = 'rental';

    public static function all(): array
    {
        return [
            self::RETAIL,
            self::SERVICE_BASED,
            self::RENTAL,
        ];
    }

    public static function labels(): array
    {
        return [
            self::RETAIL => 'Retail (products & POS)',
            self::SERVICE_BASED => 'Service-based business',
            self::RENTAL => 'Car hire / rentals',
        ];
    }

    public static function label(?string $mode): string
    {
        if ($mode === null || $mode === '') {
            return self::labels()[self::RETAIL];
        }

        return self::labels()[$mode] ?? ucfirst(str_replace('_', ' ', $mode));
    }

    public static function isRetail(?string $mode): bool
    {
        return ($mode ?: self::RETAIL) === self::RETAIL;
    }
}
