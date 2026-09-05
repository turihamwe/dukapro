<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RestaurantModeKitchenOrders extends Migration
{
    public function up()
    {
        $roles = implode("','", ['owner', 'manager', 'supervisor', 'cashier', 'waiter', 'chef', 'affiliate', 'shareholder']);
        DB::statement("ALTER TABLE users MODIFY role ENUM('{$roles}') NOT NULL DEFAULT 'owner'");

        Schema::create('kitchen_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('order_number', 40);
            $table->foreignId('waiter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('placed_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('table_label', 50)->nullable();
            $table->string('status', 30)->default('pending_kitchen');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('preparing_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'order_number']);
            $table->index(['business_id', 'branch_id', 'status']);
            $table->index(['branch_id', 'status', 'placed_at']);
        });

        Schema::create('kitchen_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kitchen_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('product_name');
            $table->string('sku', 80)->nullable();
            $table->string('measurement_unit', 50)->nullable();
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('subtotal', 14, 2);
            $table->timestamps();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('kitchen_order_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kitchen_order_id');
        });

        Schema::dropIfExists('kitchen_order_items');
        Schema::dropIfExists('kitchen_orders');

        DB::table('users')->where('role', 'chef')->update(['role' => 'cashier']);

        $roles = implode("','", ['owner', 'manager', 'supervisor', 'cashier', 'waiter', 'affiliate', 'shareholder']);
        DB::statement("ALTER TABLE users MODIFY role ENUM('{$roles}') NOT NULL DEFAULT 'owner'");
    }
}
