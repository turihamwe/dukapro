<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Business;
use App\Models\User;
use App\Support\PermissionRegistry;

class BusinessPermissionService
{
    public function allows(User $user, string $permission, ?bool $default = null): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        if (! $user->business_id) {
            return false;
        }

        $override = $this->roleOverride($user->business, (string) $user->role, $permission);
        if ($override !== null) {
            return $override;
        }

        return $default ?? PermissionRegistry::defaultGranted($permission, $user);
    }

    public function roleOverride(Business $business, string $role, string $permission): ?bool
    {
        $settings = $business->settings ?? [];
        $permissions = $settings['role_permissions'][$role] ?? null;

        if (! is_array($permissions) || ! array_key_exists($permission, $permissions)) {
            return null;
        }

        return (bool) $permissions[$permission];
    }

    public function syncRolePermissions(Business $business, array $input, ?array $rolesToUpdate = null): void
    {
        $settings = $business->settings ?? [];
        $normalized = is_array($settings['role_permissions'] ?? null) ? $settings['role_permissions'] : [];

        foreach (PermissionRegistry::definable() as $permission => $meta) {
            foreach ($meta['roles'] as $role) {
                if ($rolesToUpdate !== null && ! in_array($role, $rolesToUpdate, true)) {
                    continue;
                }

                $value = $input[$role][$permission] ?? null;
                if ($value === null) {
                    continue;
                }

                $normalized[$role][$permission] = (bool) $value;
            }
        }

        $settings['role_permissions'] = $normalized;
        $business->settings = $settings;
        $business->save();
    }

    public function activeStaffRoles(Business $business): array
    {
        $roles = User::query()
            ->where('business_id', $business->id)
            ->whereIn('role', UserRole::staffRoles())
            ->distinct()
            ->pluck('role')
            ->all();

        usort($roles, fn (string $a, string $b) => UserRole::hierarchyRank($a) <=> UserRole::hierarchyRank($b));

        return $roles;
    }

    public function matrixForBusiness(Business $business): array
    {
        $matrix = [];

        foreach (PermissionRegistry::definable() as $permission => $meta) {
            foreach ($meta['roles'] as $role) {
                $override = $this->roleOverride($business, $role, $permission);
                $default = PermissionRegistry::defaultGranted($permission, new User([
                    'role' => $role,
                    'business_id' => $business->id,
                ]));
                $matrix[$role][$permission] = $override ?? $default;
            }
        }

        return $matrix;
    }
}
