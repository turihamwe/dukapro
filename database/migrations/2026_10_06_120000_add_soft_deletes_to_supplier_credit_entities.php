<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('supplier_credit_purchases', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('supplier_credit_payments', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('supplier_credit_payments', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('supplier_credit_purchases', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
