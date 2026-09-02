<?php

namespace App\Enums;

class BusinessType
{
    public const BAR_PUB = 'bar_pub';
    public const BOUTIQUE = 'boutique_clothing';
    public const GENERAL_RETAIL = 'general_retail';
    public const HARDWARE = 'hardware';
    public const SUPERMARKET = 'supermarket';
    public const RESTAURANT = 'restaurant';
    public const PHARMACY = 'pharmacy';
    public const SALON = 'salon_spa';
    public const ELECTRONICS = 'electronics';
    public const GROCERY = 'grocery';
    public const OTHER = 'other';

    public static function all(): array
    {
        return [
            self::BAR_PUB,
            self::BOUTIQUE,
            self::GENERAL_RETAIL,
            self::HARDWARE,
            self::SUPERMARKET,
            self::RESTAURANT,
            self::PHARMACY,
            self::SALON,
            self::ELECTRONICS,
            self::GROCERY,
            self::OTHER,
        ];
    }

    public static function labels(): array
    {
        return [
            self::BAR_PUB => 'Bar / Pub',
            self::BOUTIQUE => 'Boutique / Clothing',
            self::GENERAL_RETAIL => 'General Retail',
            self::HARDWARE => 'Hardware',
            self::SUPERMARKET => 'Supermarket',
            self::RESTAURANT => 'Restaurant / Café',
            self::PHARMACY => 'Pharmacy',
            self::SALON => 'Salon / Spa',
            self::ELECTRONICS => 'Electronics',
            self::GROCERY => 'Grocery / Provisions',
            self::OTHER => 'Other',
        ];
    }

    public static function label(string $type): string
    {
        return self::labels()[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }

    public static function hospitalityTypes(): array
    {
        return [
            self::BAR_PUB,
            self::RESTAURANT,
        ];
    }

    public static function isHospitality(?string $type): bool
    {
        return $type && in_array($type, self::hospitalityTypes(), true);
    }
}
