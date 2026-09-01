<?php

namespace App\Enums;

class ShareholderStatus
{
    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const ACTIVE = 'active';
    public const COMPLETED = 'completed';
    public const REJECTED = 'rejected';
    public const SUSPENDED = 'suspended';

    public static function all(): array
    {
        return [
            self::PENDING,
            self::APPROVED,
            self::ACTIVE,
            self::COMPLETED,
            self::REJECTED,
            self::SUSPENDED,
        ];
    }

    public static function allocated(): array
    {
        return [
            self::APPROVED,
            self::ACTIVE,
            self::COMPLETED,
        ];
    }
}
