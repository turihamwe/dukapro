<?php

namespace App\Support;

use App\Enums\UserRole;

class BranchContext
{
    protected static ?int $branchId = null;

    protected static bool $forced = false;

    public static function set(?int $branchId, bool $forced = false): void
    {
        static::$branchId = $branchId;
        static::$forced = $forced;
    }

    public static function id(): ?int
    {
        if (static::$forced) {
            return static::$branchId;
        }

        if (static::$branchId !== null) {
            return static::$branchId;
        }

        $user = auth()->user();

        if (! $user || ! $user->business_id) {
            return null;
        }

        if ($user->role === UserRole::OWNER) {
            return null;
        }

        return $user->branch_id ? (int) $user->branch_id : null;
    }

    public static function clear(): void
    {
        static::$branchId = null;
        static::$forced = false;
    }
}
