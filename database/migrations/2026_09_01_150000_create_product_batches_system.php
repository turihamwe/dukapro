<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductBatchesSystem extends Migration
{
    public function up()
    {
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 15, 3);
            $table->decimal('remaining_quantity', 15, 3);
            $table->decimal('cost_price', 15, 2)->nullable();
            $table->decimal('selling_price', 15, 2);
            $table->timestamp('received_at');
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'status', 'received_at']);
            $table->index(['business_id', 'product_id']);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('cost_price', 15, 2)->nullable()->after('unit_price');
        });

        Schema::create('sale_item_batch_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 15, 3);
            $table->decimal('cost_price', 15, 2)->nullable();
            $table->decimal('selling_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->boolean('is_legacy_stock')->default(false);
            $table->timestamps();

            $table->index('sale_item_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sale_item_batch_allocations');

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('cost_price');
        });

        Schema::dropIfExists('product_batches');
    }
}
