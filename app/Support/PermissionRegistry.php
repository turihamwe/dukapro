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
            'manage-reconciliation-reports' => [
                'label' => 'Edit & delete EOD reports',
                'description' => 'Correct or remove submitted shift reconciliations',
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
            'adjust-wallet-transactions' => [
                'label' => 'Wallet ledger corrections',
                'description' => 'Correct wallet history, customer and supplier opening balances, and product opening quantities',
                'roles' => [UserRole::MANAGER],
            ],
            'impersonate-branches' => [
                'label' => 'Switch branch context',
                'description' => 'View and operate as a specific branch from the top bar',
                'roles' => [UserRole::MANAGER],
            ],
        ];
    }

    public static function visibleForBusiness(string $permission, ?\App\Models\Business $business): bool
    {
        if ($permission === 'adjust-wallet-transactions') {
            return WalletLedgerAdjustmentCompliance::isAdminUnlocked($business);
        }

        if ($permission === 'impersonate-branches') {
            return BranchImpersonationCompliance::isAdminUnlocked($business);
        }

        return true;
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

            case 'manage-reconciliation-reports':
                return $user->isManager();

            case 'view-cost-prices':
                if ($user->canSwitchToCashierMode() && CashierMode::isActive()) {
                    return false;
                }

                return $user->isManager();

            case 'manage-employees':
                return in_array($user->role, [UserRole::MANAGER, UserRole::SUPERVISOR], true);

            case 'adjust-wallet-transactions':
                return false;

            case 'impersonate-branches':
                return false;

            default:
                return false;
        }
    }
}
