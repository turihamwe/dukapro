<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CreateBranchesTable extends Migration
{
    public function up()
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['business_id', 'slug']);
            $table->index(['business_id', 'is_active']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('business_id')->constrained()->nullOnDelete();
        });

        foreach (['products', 'sales', 'expenses', 'damages', 'end_of_day_reconciliations'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->after('business_id')->constrained()->nullOnDelete();
            });
        }

        if (Schema::hasTable('shift_waiter_balances')) {
            Schema::table('shift_waiter_balances', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->after('business_id')->constrained()->nullOnDelete();
            });
        }

        $this->backfillBranches();
    }

    public function down()
    {
        foreach (['shift_waiter_balances', 'end_of_day_reconciliations', 'damages', 'expenses', 'sales', 'products'] as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'branch_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('branch_id');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });

        Schema::dropIfExists('branches');
    }

    protected function backfillBranches(): void
    {
        if (! Schema::hasTable('businesses')) {
            return;
        }

        $now = now();
        $businessIds = DB::table('businesses')->pluck('id');

        foreach ($businessIds as $businessId) {
            $branchNames = collect(['Main Branch']);

            if (Schema::hasColumn('users', 'branch_name')) {
                $branchNames = $branchNames->merge(
                    DB::table('users')
                        ->where('business_id', $businessId)
                        ->whereNotNull('branch_name')
                        ->where('branch_name', '!=', '')
                        ->pluck('branch_name')
                )->unique()->filter();
            }

            $branchMap = [];

            foreach ($branchNames as $name) {
                $slug = $this->uniqueBranchSlug((int) $businessId, Str::slug($name) ?: 'branch');
                $branchId = DB::table('branches')->insertGetId([
                    'business_id' => $businessId,
                    'name' => $name,
                    'slug' => $slug,
                    'is_active' => true,
                    'is_default' => $name === 'Main Branch',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $branchMap[$name] = $branchId;
            }

            $defaultBranchId = $branchMap['Main Branch'] ?? reset($branchMap);

            if (! $defaultBranchId) {
                continue;
            }

            foreach (['products', 'sales', 'expenses', 'damages', 'end_of_day_reconciliations'] as $tableName) {
                if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'branch_id')) {
                    DB::table($tableName)
                        ->where('business_id', $businessId)
                        ->whereNull('branch_id')
                        ->update(['branch_id' => $defaultBranchId]);
                }
            }

            if (Schema::hasTable('shift_waiter_balances') && Schema::hasColumn('shift_waiter_balances', 'branch_id')) {
                DB::table('shift_waiter_balances')
                    ->where('business_id', $businessId)
                    ->whereNull('branch_id')
                    ->update(['branch_id' => $defaultBranchId]);
            }

            if (Schema::hasColumn('users', 'branch_id')) {
                $staffUsers = DB::table('users')
                    ->where('business_id', $businessId)
                    ->whereNull('branch_id')
                    ->whereIn('role', ['manager', 'supervisor', 'cashier'])
                    ->orderBy('id')
                    ->get();

                foreach ($staffUsers as $user) {
                    $branchId = $defaultBranchId;

                    if (Schema::hasColumn('users', 'branch_name') && ! empty($user->branch_name) && isset($branchMap[$user->branch_name])) {
                        $branchId = $branchMap[$user->branch_name];
                    }

                    DB::table('users')->where('id', $user->id)->update(['branch_id' => $branchId]);
                }
            }
        }
    }

    protected function uniqueBranchSlug(int $businessId, string $base): string
    {
        $slug = $base ?: 'branch';
        $counter = 1;

        while (DB::table('branches')->where('business_id', $businessId)->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
