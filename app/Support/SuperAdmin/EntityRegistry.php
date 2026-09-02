<?php

namespace App\Support\SuperAdmin;

use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\Branch;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Shareholder;
use App\Models\ShareholderEarning;
use App\Models\User;
use App\Enums\UserRole;

class EntityRegistry
{
    public static function all(): array
    {
        return [
            'businesses' => [
                'label' => 'Businesses',
                'model' => Business::class,
                'search' => ['name', 'email', 'slug', 'portal_slug', 'phone', 'business_type'],
                'list' => ['name', 'business_type', 'email', 'subscription_status', 'created_at'],
                'creatable' => true,
                'deletable' => true,
            ],
            'branches' => [
                'label' => 'Branches',
                'model' => Branch::class,
                'search' => ['name', 'slug', 'address', 'phone'],
                'list' => ['name', 'slug', 'business_id', 'is_active', 'is_default'],
                'creatable' => true,
                'deletable' => true,
            ],
            'users' => [
                'label' => 'Users',
                'model' => User::class,
                'search' => ['name', 'email', 'username', 'role'],
                'list' => ['name', 'username', 'email', 'role', 'business_id', 'is_super_admin', 'is_sub_admin'],
                'creatable' => false,
                'deletable' => true,
            ],
            'staff' => [
                'label' => 'Staff',
                'model' => User::class,
                'scope' => function ($query) {
                    return $query->whereNotNull('business_id')
                        ->where('is_super_admin', false)
                        ->where('is_sub_admin', false)
                        ->whereIn('role', UserRole::staffRoles());
                },
                'search' => ['name', 'email', 'username', 'role'],
                'list' => ['name', 'username', 'email', 'role', 'business_id', 'branch_id'],
                'creatable' => true,
                'deletable' => true,
            ],
            'products' => [
                'label' => 'Products',
                'model' => Product::class,
                'search' => ['name', 'sku', 'measurement_unit'],
                'list' => ['name', 'sku', 'price', 'stock_quantity', 'business_id'],
                'creatable' => true,
                'deletable' => true,
            ],
            'customers' => [
                'label' => 'Customers',
                'model' => Customer::class,
                'search' => ['name', 'phone', 'email', 'company_name'],
                'list' => ['name', 'phone', 'email', 'business_id'],
                'creatable' => true,
                'deletable' => true,
            ],
            'sales' => [
                'label' => 'Sales',
                'model' => Sale::class,
                'search' => ['sale_number', 'payment_method', 'status'],
                'list' => ['sale_number', 'total', 'payment_method', 'status', 'business_id', 'completed_at'],
                'creatable' => false,
                'deletable' => true,
            ],
            'expenses' => [
                'label' => 'Expenses',
                'model' => Expense::class,
                'search' => ['title', 'category', 'description'],
                'list' => ['title', 'category', 'amount', 'expense_date', 'business_id'],
                'creatable' => true,
                'deletable' => true,
            ],
            'affiliates' => [
                'label' => 'Affiliates',
                'model' => Affiliate::class,
                'search' => ['name', 'email', 'phone', 'code', 'status'],
                'list' => ['name', 'email', 'code', 'status', 'is_active', 'commission_rate'],
                'creatable' => true,
                'deletable' => true,
            ],
            'affiliate_commissions' => [
                'label' => 'Affiliate Commissions',
                'model' => AffiliateCommission::class,
                'search' => ['status'],
                'list' => ['affiliate_id', 'business_id', 'payment_amount', 'commission_amount', 'status', 'created_at'],
                'creatable' => false,
                'deletable' => false,
            ],
            'shareholders' => [
                'label' => 'Shareholders',
                'model' => Shareholder::class,
                'search' => ['name', 'email', 'phone', 'national_id', 'status'],
                'list' => ['name', 'email', 'shares_owned', 'capital_invested', 'total_earnings', 'status', 'contract_completed'],
                'creatable' => true,
                'deletable' => true,
            ],
            'shareholder_earnings' => [
                'label' => 'Shareholder Earnings',
                'model' => ShareholderEarning::class,
                'search' => ['description', 'reference'],
                'list' => ['shareholder_id', 'amount', 'description', 'paid_at', 'created_at'],
                'creatable' => true,
                'deletable' => false,
            ],
        ];
    }

    public static function get(string $entity): ?array
    {
        return self::all()[$entity] ?? null;
    }

    public static function modelClass(string $entity): string
    {
        $config = self::get($entity);
        abort_unless($config, 404);

        return $config['model'];
    }
}
