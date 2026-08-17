<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEndOfDayReconciliationsTable extends Migration
{
    public function up()
    {
        Schema::create('end_of_day_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('reconciliation_date');
            $table->decimal('expected_cash', 15, 2)->default(0);
            $table->decimal('expected_mobile_money', 15, 2)->default(0);
            $table->decimal('actual_cash', 15, 2)->default(0);
            $table->decimal('actual_mobile_money', 15, 2)->default(0);
            $table->decimal('cash_variance', 15, 2)->default(0);
            $table->decimal('mobile_variance', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'submitted', 'approved'])->default('submitted');
            $table->timestamps();

            $table->unique(['business_id', 'user_id', 'reconciliation_date'], 'eod_recon_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('end_of_day_reconciliations');
    }
}
