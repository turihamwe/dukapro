<?php

namespace Database\Seeders;

use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $business = Business::create([
            'name' => 'DukaPro Hardware',
            'slug' => 'dukapro-hardware',
            'email' => 'shop@dukapro.test',
            'phone' => '254712345678',
            'address' => 'Nairobi, Kenya',
            'currency' => 'KES',
            'currency_symbol' => 'KES',
            'currency_position' => 'prefix',
            'is_active' => true,
            'trial_ends_at' => Carbon::now()->addDays(14),
            'subscription_status' => SubscriptionStatus::TRIAL,
            'subscription_amount' => 1500,
        ]);

        User::create([
            'business_id' => $business->id,
            'name' => 'Shop Owner',
            'email' => 'owner@dukapro.test',
            'password' => Hash::make('password'),
            'role' => UserRole::OWNER,
            'is_active' => true,
        ]);

        User::create([
            'business_id' => $business->id,
            'name' => 'Store Manager',
            'email' => 'manager@dukapro.test',
            'password' => Hash::make('password'),
            'role' => UserRole::MANAGER,
            'is_active' => true,
        ]);

        User::create([
            'business_id' => $business->id,
            'name' => 'Front Cashier',
            'email' => 'cashier@dukapro.test',
            'password' => Hash::make('password'),
            'role' => UserRole::CASHIER,
            'is_active' => true,
        ]);

        $products = [
            ['name' => 'Cement 50kg', 'sku' => 'CEM-50', 'price' => 850, 'cost_price' => 720, 'measurement_unit' => 'bag', 'stock_quantity' => 120],
            ['name' => 'Steel Nail 4"', 'sku' => 'NAIL-4', 'price' => 250, 'cost_price' => 180, 'measurement_unit' => 'kg', 'stock_quantity' => 45, 'variant_attributes' => ['size' => '4 inch', 'material' => 'steel']],
            ['name' => 'PVC Pipe 2"', 'sku' => 'PVC-2', 'price' => 450, 'cost_price' => 320, 'measurement_unit' => 'piece', 'stock_quantity' => 80, 'variant_attributes' => ['diameter' => '2 inch', 'length' => '3m']],
            ['name' => 'Paint White 20L', 'sku' => 'PNT-W20', 'price' => 3200, 'cost_price' => 2600, 'measurement_unit' => 'liter', 'stock_quantity' => 25],
            ['name' => 'Hammer Claw', 'sku' => 'TL-HAM', 'price' => 1200, 'cost_price' => 900, 'measurement_unit' => 'piece', 'stock_quantity' => 15],
        ];

        foreach ($products as $product) {
            Product::create(array_merge($product, [
                'business_id' => $business->id,
                'is_active' => true,
            ]));
        }

        Customer::create([
            'business_id' => $business->id,
            'name' => 'Juma Contractors Ltd',
            'phone' => '254722000111',
            'credit_limit' => 50000,
            'outstanding_balance' => 0,
            'is_active' => true,
        ]);

        Customer::create([
            'business_id' => $business->id,
            'name' => 'Mary Build & Supply',
            'phone' => '254733000222',
            'credit_limit' => 25000,
            'outstanding_balance' => 3500,
            'is_active' => true,
        ]);

        User::create([
            'business_id' => null,
            'name' => 'Platform SuperAdmin',
            'email' => 'superadmin@dukapro.test',
            'password' => Hash::make('password'),
            'role' => UserRole::OWNER,
            'is_active' => true,
            'is_super_admin' => true,
        ]);
    }
}
