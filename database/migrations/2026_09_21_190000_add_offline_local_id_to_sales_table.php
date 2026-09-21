<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOfflineLocalIdToSalesTable extends Migration
{
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('offline_local_id', 80)->nullable()->after('sale_number');
            $table->unique(['business_id', 'offline_local_id'], 'sales_business_offline_local_unique');
        });
    }

    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('sales_business_offline_local_unique');
            $table->dropColumn('offline_local_id');
        });
    }
}
