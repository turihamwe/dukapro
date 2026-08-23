<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddCurrencyDisplayFieldsToBusinessesTable extends Migration
{
    public function up()
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('currency_symbol', 20)->default('UGX')->after('currency');
            $table->enum('currency_position', ['prefix', 'suffix'])->default('prefix')->after('currency_symbol');
        });

        DB::table('businesses')->update([
            'currency_symbol' => DB::raw('currency'),
        ]);
    }

    public function down()
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['currency_symbol', 'currency_position']);
        });
    }
}
