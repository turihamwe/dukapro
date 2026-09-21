<?php

namespace App\Console\Commands;

use App\Models\ErrorLog;
use Illuminate\Console\Command;

class PruneErrorLogs extends Command
{
    protected $signature = 'error-logs:prune
                            {--days= : Number of days of error logs to keep (default from config)}
                            {--dry-run : Show how many rows would be deleted without deleting}
                            {--force : Delete without confirmation (used by the scheduler)}';

    protected $description = 'Delete error telemetry rows older than the retention period';

    public function handle(): int
    {
        $days = $this->option('days');
        if ($days === null || $days === '') {
            $days = config('error_tracking.retention_days', 90);
        }

        $days = (int) $days;
        if ($days < 1) {
            $this->error('Retention days must be at least 1.');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);
        $query = ErrorLog::query()->where('created_at', '<', $cutoff);
        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No error logs older than '.$days.' day(s) (before '.$cutoff->toDateTimeString().').');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info('[Dry run] Would delete '.number_format($total).' row(s) created before '.$cutoff->toDateTimeString().'.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Delete '.number_format($total).' error log row(s) created before '.$cutoff->toDateTimeString().'?', true)) {
            $this->warn('Aborted.');

            return self::SUCCESS;
        }

        $deleted = 0;
        while (true) {
            $ids = ErrorLog::query()
                ->where('created_at', '<', $cutoff)
                ->orderBy('id')
                ->limit(500)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $batch = ErrorLog::query()->whereIn('id', $ids)->delete();
            $deleted += $batch;
            $this->output->write('.');
        }

        $this->newLine();
        $this->info('Deleted '.number_format($deleted).' error log row(s).');

        return self::SUCCESS;
    }
}
