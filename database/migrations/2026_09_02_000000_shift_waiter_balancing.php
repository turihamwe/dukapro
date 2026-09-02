<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ShiftWaiterBalancing extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedBigInteger('waiter_id')->nullable()->after('user_id');
            $table->string('mobile_money_provider', 20)->nullable()->after('payment_method');
            $table->timestamp('credit_settled_at')->nullable()->after('completed_at');
            $table->string('credit_settlement_method', 30)->nullable()->after('credit_settled_at');
            $table->text('credit_settlement_notes')->nullable()->after('credit_settlement_method');

            $table->foreign('waiter_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('shift_waiter_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->date('shift_date');
            $table->unsignedBigInteger('cashier_user_id');
            $table->unsignedBigInteger('waiter_user_id');
            $table->decimal('expected_cash', 14, 2)->default(0);
            $table->decimal('expected_mobile_airtel', 14, 2)->default(0);
            $table->decimal('expected_mobile_mtn', 14, 2)->default(0);
            $table->decimal('expected_bank_other', 14, 2)->default(0);
            $table->decimal('expected_credit', 14, 2)->default(0);
            $table->decimal('actual_cash', 14, 2)->default(0);
            $table->decimal('actual_mobile_airtel', 14, 2)->default(0);
            $table->decimal('actual_mobile_mtn', 14, 2)->default(0);
            $table->decimal('actual_bank_other', 14, 2)->default(0);
            $table->decimal('actual_credit_collected', 14, 2)->default(0);
            $table->decimal('shortage', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('open');
            $table->unsignedBigInteger('end_of_day_reconciliation_id')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('cashier_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('waiter_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('end_of_day_reconciliation_id')->references('id')->on('end_of_day_reconciliations')->nullOnDelete();
            $table->unique(['business_id', 'shift_date', 'waiter_user_id'], 'shift_waiter_balances_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_waiter_balances');

        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['waiter_id']);
            $table->dropColumn([
                'waiter_id',
                'mobile_money_provider',
                'credit_settled_at',
                'credit_settlement_method',
                'credit_settlement_notes',
            ]);
        });
    }
}
