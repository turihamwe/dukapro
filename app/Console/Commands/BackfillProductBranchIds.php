<?php

namespace App\Console\Commands;

use App\Services\ProductBranchBackfillService;
use Illuminate\Console\Command;

class BackfillProductBranchIds extends Command
{
    protected $signature = 'products:backfill-branch-id
                            {--business= : Limit to a single business ID}
                            {--dry-run : Report changes without writing}';

    protected $description = 'Set null product branch_id to each business default (Main) branch';

    public function handle(ProductBranchBackfillService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $businessFilter = $this->option('business');
        $businessId = $businessFilter !== null && $businessFilter !== ''
            ? (int) $businessFilter
            : null;

        $result = $service->backfill($businessId, $dryRun);

        if ($result['businesses'] === 0 && $result['updated'] === 0 && $result['skipped'] === 0) {
            $this->info('No products with null branch_id found.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info("Dry run: would update {$result['updated']} product(s) across {$result['businesses']} business(es).");
        } else {
            $this->info("Updated {$result['updated']} product(s) across {$result['businesses']} business(es).");
        }

        if ($result['skipped'] > 0) {
            $this->warn("Skipped {$result['skipped']} product(s) with no active branch.");
        }

        return self::SUCCESS;
    }
}
