<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\CashierMode;

class PermissionRegistry
{
    public static function definable(): array
    {
        return [
            'create-inventory' => [
                'label' => 'Add products',
                'description' => 'Create new catalog items',
                'roles' => [UserRole::MANAGER, UserRole::SUPERVISOR, UserRole::CASHIER, UserRole::CHEF, UserRole::WAITER],
            ],
            'top-up-inventory' => [
                'label' => 'Top-up stock',
                'description' => 'Increase quantities of existing products',
                'roles' => [UserRole::MANAGER, UserRole::SUPERVISOR, UserRole::CASHIER],
            ],
            'update-inventory' => [
                'label' => 'Edit products',
                'description' => 'Change product details and pricing',
                'roles' => [UserRole::MANAGER, UserRole::SUPERVISOR],
            ],
            'delete-inventory' => [
                'label' => 'Delete products',
                'description' => 'Remove products from catalog',
                'roles' => [UserRole::MANAGER, UserRole::SUPERVISOR],
            ],
            'record-expenses' => [
                'label' => 'Record expenses',
                'description' => 'Log operating expenses',
                'roles' => [UserRole::MANAGER, UserRole::SUPERVISOR, UserRole::CASHIER],
            ],
            'log-damages' => [
                'label' => 'Log damages',
                'description' => 'Record stock write-offs and losses',
                'roles' => [UserRole::MANAGER, UserRole::SUPERVISOR, UserRole::CASHIER],
            ],
            'submit-reconciliation' => [
                'label' => 'Submit EOD reconciliation',
                'description' => 'Close shift and submit balancing',
                'roles' => [UserRole::MANAGER, UserRole::SUPERVISOR],
            ],
            'view-cost-prices' => [
                'label' => 'View cost prices',
                'description' => 'See purchase cost and margins',
                'roles' => [UserRole::MANAGER, UserRole::SUPERVISOR],
            ],
            'manage-employees' => [
                'label' => 'Manage staff',
                'description' => 'Add and edit team members',
                'roles' => [UserRole::MANAGER, UserRole::SUPERVISOR],
            ],
        ];
    }

    public static function defaultGranted(string $permission, User $user): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        switch ($permission) {
            case 'create-inventory':
                if ($user->isCashier()) {
                    return false;
                }
                if ($user->canSwitchToCashierMode() && CashierMode::isActive()) {
                    return false;
                }

                return in_array($user->role, [UserRole::MANAGER, UserRole::SUPERVISOR], true);

            case 'top-up-inventory':
                if ($user->canSwitchToCashierMode() && CashierMode::isActive()) {
                    return false;
                }

                return in_array($user->role, [UserRole::MANAGER, UserRole::SUPERVISOR], true);

            case 'update-inventory':
                if ($user->canSwitchToCashierMode() && CashierMode::isActive()) {
                    return false;
                }

                return $user->isManager();

            case 'delete-inventory':
                return false;

            case 'record-expenses':
                return in_array($user->role, [UserRole::MANAGER, UserRole::SUPERVISOR, UserRole::CASHIER], true);

            case 'log-damages':
                return in_array($user->role, [UserRole::MANAGER, UserRole::SUPERVISOR, UserRole::CASHIER], true);

            case 'submit-reconciliation':
                return $user->isCashier();

            case 'view-cost-prices':
                if ($user->canSwitchToCashierMode() && CashierMode::isActive()) {
                    return false;
                }

                return $user->isManager();

            case 'manage-employees':
                return in_array($user->role, [UserRole::MANAGER, UserRole::SUPERVISOR], true);

            default:
                return false;
        }
    }
}
