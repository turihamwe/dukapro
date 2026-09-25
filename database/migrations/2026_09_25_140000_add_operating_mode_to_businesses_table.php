<?php

use App\Enums\BusinessOperatingMode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOperatingModeToBusinessesTable extends Migration
{
    public function up()
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('operating_mode', 32)
                ->default(BusinessOperatingMode::RETAIL)
                ->after('business_type');
        });
    }

    public function down()
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('operating_mode');
        });
    }
}
