<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentWalletsTable extends Migration
{
    public function up()
    {
        Schema::create('payment_wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->string('name');
            $table->string('type', 32)->default('cash');
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->decimal('current_balance', 14, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->index(['business_id', 'is_active']);
        });

        Schema::table('debt_ledger_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('debt_ledger_entries', 'payment_wallet_id')) {
                $table->unsignedBigInteger('payment_wallet_id')->nullable()->after('is_opening_balance');
                $table->foreign('payment_wallet_id')->references('id')->on('payment_wallets')->nullOnDelete();
            }
        });

        Schema::table('supplier_credit_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('supplier_credit_payments', 'payment_wallet_id')) {
                $table->unsignedBigInteger('payment_wallet_id')->nullable()->after('notes');
                $table->foreign('payment_wallet_id')->references('id')->on('payment_wallets')->nullOnDelete();
            }
        });
    }

    public function down()
    {
        Schema::table('supplier_credit_payments', function (Blueprint $table) {
            if (Schema::hasColumn('supplier_credit_payments', 'payment_wallet_id')) {
                $table->dropForeign(['payment_wallet_id']);
                $table->dropColumn('payment_wallet_id');
            }
        });

        Schema::table('debt_ledger_entries', function (Blueprint $table) {
            if (Schema::hasColumn('debt_ledger_entries', 'payment_wallet_id')) {
                $table->dropForeign(['payment_wallet_id']);
                $table->dropColumn('payment_wallet_id');
            }
        });

        Schema::dropIfExists('payment_wallets');
    }
}
