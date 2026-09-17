<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExecutiveSummaryToReconciliations extends Migration
{
    public function up()
    {
        Schema::table('end_of_day_reconciliations', function (Blueprint $table) {
            $table->text('executive_summary')->nullable()->after('notes');
        });
    }

    public function down()
    {
        Schema::table('end_of_day_reconciliations', function (Blueprint $table) {
            $table->dropColumn('executive_summary');
        });
    }
}
