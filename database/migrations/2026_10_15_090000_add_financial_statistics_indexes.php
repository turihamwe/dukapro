<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->index(['business_id', 'status', 'completed_at'], 'sales_business_status_completed_idx');
        });

        Schema::table('product_batches', function (Blueprint $table) {
            $table->index(['business_id', 'received_at'], 'product_batches_business_received_idx');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_business_status_completed_idx');
        });

        Schema::table('product_batches', function (Blueprint $table) {
            $table->dropIndex('product_batches_business_received_idx');
        });
    }
};
