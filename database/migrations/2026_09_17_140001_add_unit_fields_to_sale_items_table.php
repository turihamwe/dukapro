<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUnitFieldsToSaleItemsTable extends Migration
{
    public function up()
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreignId('product_unit_id')->nullable()->after('product_id')->constrained('product_units')->nullOnDelete();
            $table->decimal('base_quantity', 15, 3)->nullable()->after('quantity');
        });
    }

    public function down()
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_unit_id');
            $table->dropColumn('base_quantity');
        });
    }
}
