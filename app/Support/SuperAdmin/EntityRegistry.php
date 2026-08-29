<?php

namespace App\Support\SuperAdmin;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;

class EntityRegistry
{
    public static function all(): array
    {
        return [
            'businesses' => [
                'label' => 'Businesses',
                'model' => Business::class,
                'search' => ['name', 'email', 'slug', 'portal_slug', 'phone'],
                'list' => ['name', 'email', 'subscription_status', 'created_at'],
                'creatable' => true,
                'deletable' => true,
            ],
            'users' => [
                'label' => 'Staff',
                'model' => User::class,
                'scope' => function ($query) {
                    return $query->whereNotNull('business_id')
                        ->where('is_super_admin', false)
                        ->where('is_sub_admin', false);
                },
                'search' => ['name', 'email', 'username', 'role'],
                'list' => ['name', 'email', 'role', 'business_id'],
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
