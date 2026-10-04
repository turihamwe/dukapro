<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoftDeletesToProductAttributes extends Migration
{
    public function up()
    {
        Schema::table('product_attributes', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('product_attribute_values', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::table('product_attribute_values', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('product_attributes', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}
