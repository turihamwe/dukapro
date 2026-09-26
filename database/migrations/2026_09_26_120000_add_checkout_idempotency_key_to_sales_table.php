<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('checkout_idempotency_key', 64)->nullable()->after('offline_local_id');
            $table->unique(['business_id', 'checkout_idempotency_key'], 'sales_business_checkout_idempotency_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('sales_business_checkout_idempotency_unique');
            $table->dropColumn('checkout_idempotency_key');
        });
    }
};
