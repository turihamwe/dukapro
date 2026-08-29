<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTotalDamagesToReconciliations extends Migration
{
    public function up()
    {
        Schema::table('end_of_day_reconciliations', function (Blueprint $table) {
            $table->decimal('total_damages', 15, 2)->default(0)->after('total_expenses');
        });
    }

    public function down()
    {
        Schema::table('end_of_day_reconciliations', function (Blueprint $table) {
            $table->dropColumn('total_damages');
        });
    }
}
