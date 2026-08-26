<?php

namespace App\Enums;

class UserRole
{
    public const OWNER = 'owner';
    public const MANAGER = 'manager';
    public const SUPERVISOR = 'supervisor';
    public const CASHIER = 'cashier';

    public static function all(): array
    {
        return [
            self::OWNER,
            self::MANAGER,
            self::SUPERVISOR,
            self::CASHIER,
        ];
    }

    public static function staffRoles(): array
    {
        return [
            self::MANAGER,
            self::SUPERVISOR,
            self::CASHIER,
        ];
    }

    public static function label(string $role): string
    {
        return ucfirst($role);
    }
}
