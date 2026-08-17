<?php

namespace App\Support;

class TenantContext
{
    protected static ?int $businessId = null;

    public static function set(?int $businessId): void
    {
        static::$businessId = $businessId;
    }

    public static function id(): ?int
    {
        if (static::$businessId !== null) {
            return static::$businessId;
        }

        $user = auth()->user();

        return $user ? $user->business_id : null;
    }

    public static function clear(): void
    {
        static::$businessId = null;
    }
}
