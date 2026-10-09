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
            $table->decimal('default_cost_price', 15, 2)->nullable()->after('cost_price');
            $table->decimal('inventory_cost_price', 15, 2)->nullable()->after('default_cost_price');
        });

        DB::table('products')->whereNotNull('cost_price')->orderBy('id')->chunkById(500, function ($products) {
            foreach ($products as $product) {
                DB::table('products')->where('id', $product->id)->update([
                    'default_cost_price' => $product->cost_price,
                    'inventory_cost_price' => $product->cost_price,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['default_cost_price', 'inventory_cost_price']);
        });
    }
};
