<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_wallet_transactions', function (Blueprint $table) {
            $table->string('source_type', 64)->nullable()->after('direction');
            $table->string('reference', 255)->nullable()->after('source_name');
            $table->text('notes')->nullable()->after('reference');
            $table->decimal('balance_after', 15, 2)->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('payment_wallet_transactions', function (Blueprint $table) {
            $table->dropColumn(['source_type', 'reference', 'notes', 'balance_after']);
        });
    }
};
