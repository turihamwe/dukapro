<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'catalog_item_type')) {
                $table->string('catalog_item_type', 32)->default('physical')->after('is_service');
            }
            if (! Schema::hasColumn('products', 'rental_rate')) {
                $table->decimal('rental_rate', 12, 2)->nullable()->after('price');
            }
            if (! Schema::hasColumn('products', 'rental_rate_unit')) {
                $table->string('rental_rate_unit', 16)->nullable()->after('rental_rate');
            }
        });

        if (Schema::hasColumn('products', 'is_service')) {
            DB::table('products')->where('is_service', true)->update(['catalog_item_type' => 'service']);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'rental_rate_unit')) {
                $table->dropColumn('rental_rate_unit');
            }
            if (Schema::hasColumn('products', 'rental_rate')) {
                $table->dropColumn('rental_rate');
            }
            if (Schema::hasColumn('products', 'catalog_item_type')) {
                $table->dropColumn('catalog_item_type');
            }
        });
    }
};
