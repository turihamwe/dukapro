<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoftDeletesToEndOfDayReconciliations extends Migration
{
    public function up()
    {
        Schema::table('end_of_day_reconciliations', function (Blueprint $table) {
            $table->dropUnique('eod_recon_unique');
            $table->softDeletes();
            $table->index(['business_id', 'user_id', 'reconciliation_date'], 'eod_recon_lookup');
        });
    }

    public function down()
    {
        Schema::table('end_of_day_reconciliations', function (Blueprint $table) {
            $table->dropIndex('eod_recon_lookup');
            $table->dropSoftDeletes();
            $table->unique(['business_id', 'user_id', 'reconciliation_date'], 'eod_recon_unique');
        });
    }
}
