<?php

use App\Models\Business;
use App\Services\ExpenseService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ExpenseCategoriesAndEodBalancing extends Migration
{
    public function up()
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['business_id', 'slug']);
            $table->index(['business_id', 'is_active']);
        });

        foreach (Business::query()->pluck('id') as $businessId) {
            foreach (ExpenseService::DEFAULT_CATEGORIES as $slug => $name) {
                DB::table('expense_categories')->insert([
                    'business_id' => $businessId,
                    'name' => $name,
                    'slug' => $slug,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::table('end_of_day_reconciliations', function (Blueprint $table) {
            $table->decimal('expected_bank_other', 15, 2)->default(0)->after('expected_mobile_money');
            $table->decimal('actual_bank_other', 15, 2)->default(0)->after('actual_mobile_money');
            $table->decimal('missing_money', 15, 2)->default(0)->after('mobile_variance');
        });
    }

    public function down()
    {
        Schema::table('end_of_day_reconciliations', function (Blueprint $table) {
            $table->dropColumn(['expected_bank_other', 'actual_bank_other', 'missing_money']);
        });

        Schema::dropIfExists('expense_categories');
    }
}
