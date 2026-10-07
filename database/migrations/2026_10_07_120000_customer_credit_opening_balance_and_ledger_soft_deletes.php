<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CustomerCreditOpeningBalanceAndLedgerSoftDeletes extends Migration
{
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'opening_balance')) {
                $table->decimal('opening_balance', 14, 2)->default(0)->after('outstanding_balance');
            }
        });

        Schema::table('debt_ledger_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('debt_ledger_entries', 'is_opening_balance')) {
                $table->boolean('is_opening_balance')->default(false)->after('due_date');
            }
            if (! Schema::hasColumn('debt_ledger_entries', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down()
    {
        Schema::table('debt_ledger_entries', function (Blueprint $table) {
            if (Schema::hasColumn('debt_ledger_entries', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            if (Schema::hasColumn('debt_ledger_entries', 'is_opening_balance')) {
                $table->dropColumn('is_opening_balance');
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'opening_balance')) {
                $table->dropColumn('opening_balance');
            }
        });
    }
}
