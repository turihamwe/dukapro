<?php

namespace App\Support;

use App\Models\User;

class PlatformAdmin
{
    public static function canAccess(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isSubAdmin();
    }

    public static function canMutate(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public static function canCreate(User $user): bool
    {
        return self::canAccess($user);
    }

    public static function readOnly(User $user): bool
    {
        return $user->isSubAdmin();
    }
}
