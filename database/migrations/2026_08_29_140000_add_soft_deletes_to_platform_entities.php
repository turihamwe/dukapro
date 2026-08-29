<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoftDeletesToPlatformEntities extends Migration
{
    public function up()
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}
