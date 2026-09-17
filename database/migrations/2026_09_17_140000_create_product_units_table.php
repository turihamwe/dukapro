<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductUnitsTable extends Migration
{
    public function up()
    {
        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('unit_name', 50);
            $table->decimal('conversion_factor', 15, 6)->default(1);
            $table->boolean('is_base_unit')->default(false);
            $table->decimal('price', 15, 2)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'unit_name']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_units');
    }
}
