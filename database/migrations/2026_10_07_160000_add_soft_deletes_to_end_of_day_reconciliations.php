<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoftDeletesToEndOfDayReconciliations extends Migration
{
    public function up()
    {
        Schema::table('end_of_day_reconciliations', function (Blueprint $table) {
            // Add softDeletes safely only if the column doesn't exist yet
            if (!Schema::hasColumn('end_of_day_reconciliations', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down()
    {
        Schema::table('end_of_day_reconciliations', function (Blueprint $table) {
            if (Schema::hasColumn('end_of_day_reconciliations', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
}