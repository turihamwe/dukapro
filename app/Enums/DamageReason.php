<?php

namespace App\Enums;

class DamageReason
{
    public const BROKEN = 'broken';
    public const EXPIRED = 'expired';
    public const SPILLED = 'spilled';
    public const OTHER = 'other';

    public static function all(): array
    {
        return [
            self::BROKEN,
            self::EXPIRED,
            self::SPILLED,
            self::OTHER,
        ];
    }

    public static function labels(): array
    {
        return [
            self::BROKEN => 'Broken',
            self::EXPIRED => 'Expired',
            self::SPILLED => 'Spilled',
            self::OTHER => 'Other',
        ];
    }
}
