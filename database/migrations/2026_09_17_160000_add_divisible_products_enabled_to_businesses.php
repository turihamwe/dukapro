<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDivisibleProductsEnabledToBusinesses extends Migration
{
    public function up()
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->boolean('divisible_products_enabled')->default(false)->after('variable_pricing_enabled');
        });
    }

    public function down()
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('divisible_products_enabled');
        });
    }
}
