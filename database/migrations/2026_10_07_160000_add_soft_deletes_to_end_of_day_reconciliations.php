<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoftDeletesToEndOfDayReconciliations extends Migration
{
    public function up()
    {
        Schema::table('end_of_day_reconciliations', function (Blueprint $table) {
            // Safely drop unique index if it exists
            try {
                $table->dropUnique('eod_recon_unique');
            } catch (\Exception $e) {
                // Index might already be dropped, continue safely
            }

            // Add softDeletes only if the column doesn't exist yet
            if (!Schema::hasColumn('end_of_day_reconciliations', 'deleted_at')) {
                $table->softDeletes();
            }

            // Safely add lookup index if it doesn't already exist
            // (Note: checking index existence in Laravel can vary, using try-catch is safest)
            try {
                $table->index(['business_id', 'user_id', 'reconciliation_date'], 'eod_recon_lookup');
            } catch (\Exception $e) {
                // Index might already exist
            }
        });
    }

    public function down()
    {
        Schema::table('end_of_day_reconciliations', function (Blueprint $table) {
            try {
                $table->dropIndex('eod_recon_lookup');
            } catch (\Exception $e) {}

            if (Schema::hasColumn('end_of_day_reconciliations', 'deleted_at')) {
                $table->dropSoftDeletes();
            }

            try {
                $table->unique(['business_id', 'user_id', 'reconciliation_date'], 'eod_recon_unique');
            } catch (\Exception $e) {}
        });
    }
}