<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_waiter_rosters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->date('shift_date');
            $table->foreignId('waiter_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('selected_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['business_id', 'shift_date', 'waiter_user_id'], 'shift_waiter_rosters_unique_waiter');
            $table->index(['business_id', 'shift_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_waiter_rosters');
    }
};
