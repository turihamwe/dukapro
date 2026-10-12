<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_credit_purchases', function (Blueprint $table) {
            $table->boolean('is_historical_adjustment')->default(false)->after('is_opening_balance');
        });

        Schema::create('payment_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('direction', 16)->default('in');
            $table->decimal('amount', 15, 2);
            $table->string('category', 64);
            $table->string('source_name', 500)->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['business_id', 'occurred_at']);
            $table->index(['payment_wallet_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_wallet_transactions');

        Schema::table('supplier_credit_purchases', function (Blueprint $table) {
            $table->dropColumn('is_historical_adjustment');
        });
    }
};
