<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBranchStockTransfersTable extends Migration
{
    public function up()
    {
        Schema::create('branch_stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('to_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('from_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('to_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 15, 3);
            $table->timestamps();

            $table->index(['business_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('branch_stock_transfers');
    }
}
