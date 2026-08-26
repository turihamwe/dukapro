<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PlatformEnhancements extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('critical_threshold')->default(5)->after('stock_quantity');
            $table->softDeletes();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('is_credit_customer')->default(false)->after('is_active');
            $table->string('company_name')->nullable()->after('name');
            $table->text('notes')->nullable()->after('address');
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('damages', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::table('damages', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['is_credit_customer', 'company_name', 'notes']);
            $table->dropSoftDeletes();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('critical_threshold');
            $table->dropSoftDeletes();
        });
    }
}
