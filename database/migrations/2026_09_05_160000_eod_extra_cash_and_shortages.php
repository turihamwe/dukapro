<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('end_of_day_reconciliations', function (Blueprint $table) {
            $table->decimal('extra_cash', 15, 2)->default(0)->after('total_damages');
        });

        Schema::create('reconciliation_shortages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('shortage_date');
            $table->decimal('amount', 14, 2);
            $table->decimal('amount_settled', 14, 2)->default(0);
            $table->string('status', 20)->default('pending');
            $table->string('source', 30);
            $table->foreignId('end_of_day_reconciliation_id')->nullable()->constrained('end_of_day_reconciliations')->nullOnDelete();
            $table->foreignId('shift_waiter_balance_id')->nullable()->constrained('shift_waiter_balances')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('settled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('settled_at')->nullable();
            $table->text('settlement_notes')->nullable();
            $table->timestamps();

            $table->unique(['shift_waiter_balance_id'], 'recon_shortages_waiter_balance_unique');
            $table->index(['business_id', 'status', 'shortage_date'], 'recon_shortages_business_status_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_shortages');

        Schema::table('end_of_day_reconciliations', function (Blueprint $table) {
            $table->dropColumn('extra_cash');
        });
    }
};
