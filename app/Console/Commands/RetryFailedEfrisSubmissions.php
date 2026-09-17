<?php

namespace App\Console\Commands;

use App\Jobs\SubmitSaleToEfrisJob;
use App\Models\Sale;
use App\Support\EfrisCompliance;
use Illuminate\Console\Command;

class RetryFailedEfrisSubmissions extends Command
{
    protected $signature = 'efris:retry-failed {--business=}';

    protected $description = 'Re-queue failed or pending EFRIS fiscal receipt submissions';

    public function handle(): int
    {
        if (! EfrisCompliance::globallyEnabled()) {
            $this->warn('EFRIS is disabled platform-wide (Use EFRIS is off).');

            return self::SUCCESS;
        }

        $query = Sale::query()
            ->withoutGlobalScopes()
            ->whereIn('efris_status', ['failed', 'pending']);

        if ($businessId = $this->option('business')) {
            $query->where('business_id', $businessId);
        }

        $count = 0;

        $query->orderBy('id')->chunkById(100, function ($sales) use (&$count) {
            foreach ($sales as $sale) {
                SubmitSaleToEfrisJob::dispatch($sale->id);
                $count++;
            }
        });

        $this->info('Queued ' . $count . ' EFRIS submission job(s).');

        return self::SUCCESS;
    }
}
