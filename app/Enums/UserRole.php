<?php

namespace App\Enums;

class UserRole
{
    public const OWNER = 'owner';
    public const MANAGER = 'manager';
    public const CASHIER = 'cashier';

    public static function all(): array
    {
        return [
            self::OWNER,
            self::MANAGER,
            self::CASHIER,
        ];
    }
}
