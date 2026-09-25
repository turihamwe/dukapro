<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('businesses', 'operating_mode')) {
            DB::table('businesses')
                ->where('operating_mode', 'inventory_only')
                ->update(['operating_mode' => 'retail']);
        }

        if (Schema::hasColumn('products', 'catalog_item_type')) {
            DB::table('products')
                ->where('catalog_item_type', 'inventory_only')
                ->update([
                    'catalog_item_type' => 'physical',
                    'is_sellable' => true,
                ]);
        }
    }

    public function down(): void
    {
        // Data migration is not reversed.
    }
};
