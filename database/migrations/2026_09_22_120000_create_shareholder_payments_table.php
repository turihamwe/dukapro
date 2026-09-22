<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shareholder_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shareholder_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('payment_method')->default('mobile_money');
            $table->string('reference')->unique();
            $table->string('provider')->default('mtn_momo');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['shareholder_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shareholder_payments');
    }
};
