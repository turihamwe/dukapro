<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RestaurantTablesAndItemNotes extends Migration
{
    public function up()
    {
        Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('code', 30)->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['branch_id', 'name']);
            $table->index(['business_id', 'branch_id', 'is_active']);
        });

        Schema::table('kitchen_orders', function (Blueprint $table) {
            $table->foreignId('restaurant_table_id')->nullable()->after('placed_by_user_id')->constrained()->nullOnDelete();
        });

        Schema::table('kitchen_order_items', function (Blueprint $table) {
            $table->string('notes', 500)->nullable()->after('subtotal');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->string('notes', 500)->nullable()->after('subtotal');
        });
    }

    public function down()
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('notes');
        });

        Schema::table('kitchen_order_items', function (Blueprint $table) {
            $table->dropColumn('notes');
        });

        Schema::table('kitchen_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('restaurant_table_id');
        });

        Schema::dropIfExists('restaurant_tables');
    }
}
