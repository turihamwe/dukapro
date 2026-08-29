<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnhanceExpensesAndEodForDailyBalancing extends Migration
{
    public function up()
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('title')->nullable()->after('user_id');
        });

        Schema::table('end_of_day_reconciliations', function (Blueprint $table) {
            $table->decimal('total_sales', 15, 2)->default(0)->after('mobile_variance');
            $table->decimal('total_expenses', 15, 2)->default(0)->after('total_sales');
            $table->decimal('net_income', 15, 2)->default(0)->after('total_expenses');
        });

        DB::table('expenses')->whereNull('title')->update([
            'title' => DB::raw('description'),
        ]);
    }

    public function down()
    {
        Schema::table('end_of_day_reconciliations', function (Blueprint $table) {
            $table->dropColumn(['total_sales', 'total_expenses', 'net_income']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('title');
        });
    }
}
