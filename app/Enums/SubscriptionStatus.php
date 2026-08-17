<?php

namespace App\Enums;

class SubscriptionStatus
{
    public const TRIAL = 'trial';
    public const ACTIVE = 'active';
    public const INACTIVE = 'inactive';
    public const EXPIRED = 'expired';

    public static function all(): array
    {
        return [
            self::TRIAL,
            self::ACTIVE,
            self::INACTIVE,
            self::EXPIRED,
        ];
    }
}
