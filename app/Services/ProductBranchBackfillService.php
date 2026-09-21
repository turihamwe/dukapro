<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductBranchBackfillService
{
    /**
     * @return array{updated: int, skipped: int, businesses: int}
     */
    public function backfill(?int $businessId = null, bool $dryRun = false): array
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'branch_id')) {
            return ['updated' => 0, 'skipped' => 0, 'businesses' => 0];
        }

        if (! Schema::hasTable('branches')) {
            return ['updated' => 0, 'skipped' => 0, 'businesses' => 0];
        }

        $businessQuery = DB::table('products')
            ->whereNull('branch_id')
            ->distinct()
            ->orderBy('business_id');

        if ($businessId !== null) {
            $businessQuery->where('business_id', $businessId);
        }

        $businessIds = $businessQuery->pluck('business_id');
        $updated = 0;
        $skipped = 0;

        foreach ($businessIds as $id) {
            $branchId = $this->defaultBranchIdForBusiness((int) $id);

            if (! $branchId) {
                $skipped += (int) DB::table('products')
                    ->where('business_id', $id)
                    ->whereNull('branch_id')
                    ->count();

                continue;
            }

            if ($dryRun) {
                $updated += (int) DB::table('products')
                    ->where('business_id', $id)
                    ->whereNull('branch_id')
                    ->count();

                continue;
            }

            $updated += DB::table('products')
                ->where('business_id', $id)
                ->whereNull('branch_id')
                ->update(['branch_id' => $branchId]);
        }

        return [
            'updated' => $updated,
            'skipped' => $skipped,
            'businesses' => $businessIds->count(),
        ];
    }

    protected function defaultBranchIdForBusiness(int $businessId): ?int
    {
        $query = DB::table('branches')
            ->where('business_id', $businessId)
            ->where('is_active', true);

        if (Schema::hasColumn('branches', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $branchId = $query
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->value('id');

        return $branchId ? (int) $branchId : null;
    }
}
