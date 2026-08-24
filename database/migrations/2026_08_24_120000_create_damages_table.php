<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDamagesTable extends Migration
{
    public function up()
    {
        Schema::create('damages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 15, 3);
            $table->string('reason', 50);
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->date('damage_date');
            $table->timestamps();

            $table->index(['business_id', 'damage_date']);
            $table->index(['product_id', 'damage_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('damages');
    }
}
