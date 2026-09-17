<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompanionReceiptIssuedToSales extends Migration
{
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('companion_receipt_issued')->default(false)->after('is_credit_sale');
        });
    }

    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('companion_receipt_issued');
        });
    }
}
