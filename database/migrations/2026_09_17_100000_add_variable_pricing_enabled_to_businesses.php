<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVariablePricingEnabledToBusinesses extends Migration
{
    public function up()
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->boolean('variable_pricing_enabled')->default(false)->after('employees_onboarding_complete');
        });
    }

    public function down()
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('variable_pricing_enabled');
        });
    }
}
