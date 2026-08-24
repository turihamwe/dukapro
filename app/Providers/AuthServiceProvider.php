<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Damage;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Policies\AuditLogPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\DamagePolicy;
use App\Policies\ExpensePolicy;
use App\Policies\ProductPolicy;
use App\Policies\SalePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Product::class => ProductPolicy::class,
        Customer::class => CustomerPolicy::class,
        Sale::class => SalePolicy::class,
        AuditLog::class => AuditLogPolicy::class,
        Expense::class => ExpensePolicy::class,
        Damage::class => DamagePolicy::class,
    ];

    public function boot()
    {
        $this->registerPolicies();

        Gate::define('view-dashboard', function (User $user) {
            return in_array($user->role, [UserRole::OWNER, UserRole::MANAGER], true);
        });

        Gate::define('view-analytics', function (User $user) {
            return $user->isOwner();
        });

        Gate::define('view-profit-margins', function (User $user) {
            return $user->isOwner();
        });

        Gate::define('manage-settings', function (User $user) {
            return $user->isOwner();
        });

        Gate::define('manage-billing', function (User $user) {
            return $user->isOwner();
        });

        Gate::define('view-audit-logs', function (User $user) {
            return $user->isOwner();
        });

        Gate::define('view-cost-prices', function (User $user) {
            return $user->isOwner() || $user->isManager();
        });

        Gate::define('manage-inventory', function (User $user) {
            return $user->isOwner() || $user->isManager();
        });

        Gate::define('manage-debts', function (User $user) {
            return $user->isOwner() || $user->isManager();
        });

        Gate::define('view-all-reconciliations', function (User $user) {
            return $user->isOwner() || $user->isManager();
        });

        Gate::define('access-pos', function (User $user) {
            return in_array($user->role, UserRole::all(), true);
        });

        Gate::define('submit-reconciliation', function (User $user) {
            return in_array($user->role, UserRole::all(), true);
        });

        Gate::define('log-damages', function (User $user) {
            return in_array($user->role, UserRole::all(), true);
        });

        Gate::define('access-superadmin', function (User $user) {
            return $user->isSuperAdmin();
        });
    }
}
