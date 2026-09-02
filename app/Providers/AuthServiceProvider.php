<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Brand;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Damage;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SoldByUnit;
use App\Models\User;
use App\Policies\BranchPolicy;
use App\Policies\BrandPolicy;
use App\Policies\AuditLogPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\DamagePolicy;
use App\Policies\ExpensePolicy;
use App\Policies\ExpenseCategoryPolicy;
use App\Policies\ProductPolicy;
use App\Policies\SalePolicy;
use App\Policies\SoldByUnitPolicy;
use App\Policies\UserPolicy;
use App\Support\CashierMode;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Branch::class => BranchPolicy::class,
        Product::class => ProductPolicy::class,
        Brand::class => BrandPolicy::class,
        SoldByUnit::class => SoldByUnitPolicy::class,
        Customer::class => CustomerPolicy::class,
        Sale::class => SalePolicy::class,
        User::class => UserPolicy::class,
        AuditLog::class => AuditLogPolicy::class,
        Expense::class => ExpensePolicy::class,
        ExpenseCategory::class => ExpenseCategoryPolicy::class,
        Damage::class => DamagePolicy::class,
    ];

    public function boot()
    {
        $this->registerPolicies();

        Gate::define('view-dashboard', function (User $user) {
            return in_array($user->role, [UserRole::OWNER, UserRole::MANAGER, UserRole::SUPERVISOR], true);
        });

        Gate::define('view-analytics', function (User $user) {
            return $user->isOwner() || $user->isManager();
        });

        Gate::define('view-profit-margins', function (User $user) {
            return $user->isOwner() || $user->isManager();
        });

        Gate::define('manage-settings', function (User $user) {
            return $user->isOwner();
        });

        Gate::define('manage-branches', function (User $user) {
            return $user->isOwner();
        });

        Gate::define('manage-billing', function (User $user) {
            return $user->isOwner();
        });

        Gate::define('view-audit-logs', function (User $user) {
            return $user->isOwner();
        });

        Gate::define('view-cost-prices', function (User $user) {
            if ($user->canSwitchToCashierMode() && CashierMode::isActive()) {
                return false;
            }

            return $user->isOwner() || $user->isManager();
        });

        Gate::define('view-inventory', function (User $user) {
            return in_array($user->role, [UserRole::OWNER, UserRole::MANAGER, UserRole::SUPERVISOR, UserRole::CASHIER], true);
        });

        Gate::define('create-inventory', function (User $user) {
            if ($user->isCashier()) {
                return false;
            }

            if ($user->canSwitchToCashierMode() && CashierMode::isActive()) {
                return false;
            }

            return in_array($user->role, [UserRole::OWNER, UserRole::MANAGER, UserRole::SUPERVISOR], true);
        });

        Gate::define('update-inventory', function (User $user) {
            if ($user->canSwitchToCashierMode() && CashierMode::isActive()) {
                return false;
            }

            return $user->isOwner() || $user->isManager();
        });

        Gate::define('delete-inventory', function (User $user) {
            if ($user->canSwitchToCashierMode() && CashierMode::isActive()) {
                return false;
            }

            return $user->isOwner();
        });

        Gate::define('manage-inventory', function (User $user) {
            return $user->can('create-inventory') || $user->can('update-inventory');
        });

        Gate::define('manage-debts', function (User $user) {
            return $user->isOwner() || $user->isManager();
        });

        Gate::define('view-customers', function (User $user) {
            return $user->isOwner() || $user->isManager() || $user->isSupervisor();
        });

        Gate::define('view-sales-reports', function (User $user) {
            return $user->isOwner() || $user->isManager() || $user->isSupervisor();
        });

        Gate::define('view-all-reconciliations', function (User $user) {
            if ($user->canSwitchToCashierMode() && CashierMode::isActive()) {
                return false;
            }

            return $user->isOwner() || $user->isManager() || $user->isSupervisor();
        });

        Gate::define('switch-cashier-mode', function (User $user) {
            return $user->canSwitchToCashierMode();
        });

        Gate::define('access-pos', function (User $user) {
            if ($user->isCashier()) {
                return true;
            }

            return $user->canSwitchToCashierMode() && CashierMode::isActive();
        });

        Gate::define('submit-reconciliation', function (User $user) {
            if ($user->isCashier()) {
                return true;
            }

            return $user->canSwitchToCashierMode() && CashierMode::isActive();
        });

        Gate::define('view-reconciliation-history', function (User $user) {
            return in_array($user->role, UserRole::all(), true);
        });

        Gate::define('log-damages', function (User $user) {
            if ($user->isCashier()) {
                return true;
            }

            if ($user->canSwitchToCashierMode() && CashierMode::isActive()) {
                return true;
            }

            return $user->isOwner() || $user->isManager();
        });

        Gate::define('manage-employees', function (User $user) {
            return $user->isOwner() || $user->isManager() || $user->isSupervisor();
        });

        Gate::define('manage-profile', function (User $user) {
            return (bool) $user->business_id;
        });

        Gate::define('manage-expenses', function (User $user) {
            return $user->isOwner() || $user->isManager();
        });

        Gate::define('view-expenses', function (User $user) {
            return $user->isOwner() || $user->isManager() || $user->isSupervisor();
        });

        Gate::define('record-expenses', function (User $user) {
            return $user->isOwner()
                || $user->isManager()
                || $user->isSupervisor()
                || $user->isCashier();
        });

        Gate::define('access-superadmin', function (User $user) {
            return $user->isPlatformAdmin();
        });

        Gate::define('platform-full-access', function (User $user) {
            return $user->isSuperAdmin();
        });
    }
}
