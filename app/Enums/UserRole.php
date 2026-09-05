<?php

namespace App\Enums;

class UserRole
{
    public const OWNER = 'owner';
    public const MANAGER = 'manager';
    public const SUPERVISOR = 'supervisor';
    public const CASHIER = 'cashier';
    public const WAITER = 'waiter';
    public const AFFILIATE = 'affiliate';
    public const SHAREHOLDER = 'shareholder';

    public static function all(): array
    {
        return [
            self::OWNER,
            self::MANAGER,
            self::SUPERVISOR,
            self::CASHIER,
            self::WAITER,
            self::AFFILIATE,
            self::SHAREHOLDER,
        ];
    }

    public static function staffRoles(): array
    {
        return [
            self::MANAGER,
            self::SUPERVISOR,
            self::CASHIER,
            self::WAITER,
        ];
    }

    /**
     * Operational floor staff eligible for waiter / shift order assignment.
     */
    public static function floorStaffRoles(): array
    {
        return [
            self::WAITER,
        ];
    }

    public static function isFloorStaff(string $role): bool
    {
        return in_array($role, self::floorStaffRoles(), true);
    }

    /**
     * Staff roles an actor may assign (strictly below their own rank).
     */
    public static function rolesAssignableBy(string $actorRole): array
    {
        if ($actorRole === self::OWNER) {
            return self::staffRoles();
        }

        $actorRank = self::hierarchyRank($actorRole);

        return array_values(array_filter(
            self::staffRoles(),
            fn (string $role) => self::hierarchyRank($role) > $actorRank
        ));
    }

    public static function canManageRole(string $actorRole, string $targetRole): bool
    {
        if ($targetRole === self::OWNER) {
            return false;
        }

        if ($actorRole === self::OWNER) {
            return in_array($targetRole, self::staffRoles(), true);
        }

        return in_array($targetRole, self::staffRoles(), true)
            && self::hierarchyRank($targetRole) > self::hierarchyRank($actorRole);
    }

    /**
     * Lower number = higher rank. Used to enforce assignment hierarchy.
     */
    public static function hierarchyRank(string $role): int
    {
        return [
            self::OWNER => 0,
            self::MANAGER => 1,
            self::SUPERVISOR => 2,
            self::CASHIER => 3,
            self::WAITER => 4,
            self::AFFILIATE => 99,
            self::SHAREHOLDER => 99,
        ][$role] ?? 100;
    }

    public static function floorStaffLabel(string $role): string
    {
        return self::label($role);
    }

    public static function label(string $role): string
    {
        $labels = [
            self::WAITER => 'Waiter',
        ];

        return $labels[$role] ?? ucfirst($role);
    }
}
