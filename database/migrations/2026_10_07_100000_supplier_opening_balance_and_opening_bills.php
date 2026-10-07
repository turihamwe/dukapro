<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->decimal('opening_balance', 15, 2)->default(0)->after('is_active');
        });

        Schema::table('supplier_credit_purchases', function (Blueprint $table) {
            $table->boolean('is_opening_balance')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_credit_purchases', function (Blueprint $table) {
            $table->dropColumn('is_opening_balance');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('opening_balance');
        });
    }
};
