<?php

namespace App\Support;

use App\Models\Business;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EfrisGoLiveReadiness
{
    public function forBusiness(Business $business): array
    {
        $business->loadMissing('efrisSetting');
        $efris = $business->efrisSetting;
        $tin = $efris ? $efris->resolveTin($business) : null;

        $checks = [];
        $checks[] = $this->check(
            'Platform EFRIS enabled',
            EfrisCompliance::globallyEnabled(),
            'Turn on Use EFRIS in superadmin system settings.',
            'Available platform-wide.'
        );
        $checks[] = $this->check(
            'Business unlocked for EFRIS',
            EfrisCompliance::isAdminUnlocked($efris),
            'Unlock EFRIS for this tenant below.',
            'Owner can see EFRIS in Settings.'
        );
        $checks[] = $this->check(
            'Owner enabled fiscal receipts',
            EfrisCompliance::isOwnerEnabled($business),
            'Owner must enable EFRIS in business Settings.',
            'POS can show the per-sale EFRIS checkbox.'
        );
        $checks[] = $this->check(
            'Company TIN on file',
            filled($tin),
            'Add Tax / registration number on the business profile.',
            $tin ? 'TIN: ' . $tin : null
        );
        $hasToken = $efris && $efris->hasStoredToken();
        $checks[] = $this->check(
            'WEAF API token stored',
            $hasToken,
            'Owner should use Connect EFRIS or paste a token in Settings.',
            $hasToken ? 'Token present (encrypted).' : null
        );
        $provisioned = $efris && $efris->isProvisioned();
        $checks[] = $this->check(
            'WEAF connection',
            $provisioned,
            optional($efris)->provisioning_error ?: 'Complete Connect EFRIS in owner Settings.',
            $efris && $efris->provisioning_status
                ? ucwords(str_replace('_', ' ', $efris->provisioning_status))
                : 'Not started'
        );
        $transmission = EfrisCompliance::isTransmissionAllowed($business);
        $checks[] = $this->check(
            'Ready to transmit receipts',
            $transmission,
            'Fix failed checks above (TIN, token, owner toggle).',
            $transmission ? 'Checkout can submit to WEAF when cashier opts in.' : null
        );

        $environment = strtolower((string) optional($efris)->efris_environment ?: 'sandbox');
        $isProduction = $environment === 'production';
        $checks[] = $this->check(
            'Production environment',
            $isProduction,
            'Sandbox is fine for demos. Switch to Production in owner Settings after WEAF onboarding.',
            $isProduction ? 'Production' : 'Sandbox (demo / test)',
            $isProduction ? 'pass' : 'warn'
        );

        $catalog = $this->catalogStats($business);
        $mappedPct = $catalog['mapped_percent'];
        $checks[] = $this->check(
            'Product EFRIS codes',
            $catalog['sellable_count'] === 0 || $mappedPct >= 80,
            $catalog['sellable_count'] === 0
                ? 'No sellable products yet.'
                : sprintf('Only %s%% have efris_item_code — map codes before go-live.', $mappedPct),
            sprintf(
                '%s / %s sellable products have EFRIS item code (%s%%).',
                $catalog['with_efris_code'],
                $catalog['sellable_count'],
                $mappedPct
            ),
            $mappedPct >= 80 || $catalog['sellable_count'] === 0 ? 'pass' : ($mappedPct >= 40 ? 'warn' : 'fail')
        );

        $submissions = $this->submissionStats($business);
        $checks[] = $this->check(
            'Recent fiscal submissions',
            $submissions['failed'] === 0,
            $submissions['failed'] > 0
                ? sprintf('%s failed submission(s) in last 90 days — run efris:retry-failed or fix catalog.', $submissions['failed'])
                : 'No successful fiscal receipt yet — complete a sandbox sale with EFRIS checked.',
            sprintf(
                'Requested: %s · Success: %s · Pending: %s · Failed: %s (90 days)',
                $submissions['requested'],
                $submissions['success'],
                $submissions['pending'],
                $submissions['failed']
            ),
            $submissions['failed'] > 0 ? 'fail' : ($submissions['success'] > 0 ? 'pass' : 'warn')
        );

        $queue = $this->queueStats($business);
        $checks[] = $this->check(
            'Submission queue',
            $queue['status'] !== 'fail',
            $queue['hint'],
            $queue['detail'],
            $queue['status']
        );

        $passed = collect($checks)->where('status', 'pass')->count();
        $total = count($checks);
        $score = $total > 0 ? (int) round(($passed / $total) * 100) : 0;

        $overall = 'attention';
        if (! EfrisCompliance::globallyEnabled() || ! EfrisCompliance::isAdminUnlocked($efris)) {
            $overall = 'blocked';
        } elseif ($transmission && $isProduction && $mappedPct >= 80 && $submissions['failed'] === 0) {
            $overall = 'production';
        } elseif ($transmission && $submissions['success'] > 0) {
            $overall = 'sandbox';
        }

        return [
            'checks' => $checks,
            'score' => $score,
            'overall' => $overall,
            'catalog' => $catalog,
            'submissions' => $submissions,
            'queue' => $queue,
            'tin' => $tin,
            'environment' => $environment,
            'transmission_allowed' => $transmission,
        ];
    }

    protected function catalogStats(Business $business): array
    {
        $base = Product::query()
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->sellable();

        $sellableCount = (int) (clone $base)->count();
        $withCode = (int) (clone $base)->whereNotNull('efris_item_code')->where('efris_item_code', '!=', '')->count();
        $withSku = (int) (clone $base)->whereNotNull('sku')->where('sku', '!=', '')->count();
        $pct = $sellableCount > 0 ? (int) round(($withCode / $sellableCount) * 100) : 0;

        return [
            'sellable_count' => $sellableCount,
            'with_efris_code' => $withCode,
            'with_sku' => $withSku,
            'mapped_percent' => $pct,
        ];
    }

    protected function submissionStats(Business $business): array
    {
        $since = now()->subDays(90);

        $requested = (int) Sale::query()
            ->where('business_id', $business->id)
            ->where('efris_requested', true)
            ->where('completed_at', '>=', $since)
            ->count();

        $success = (int) Sale::query()
            ->where('business_id', $business->id)
            ->where('efris_status', 'success')
            ->where('completed_at', '>=', $since)
            ->count();

        $pending = (int) Sale::query()
            ->where('business_id', $business->id)
            ->where('efris_status', 'pending')
            ->count();

        $failed = (int) Sale::query()
            ->where('business_id', $business->id)
            ->where('efris_status', 'failed')
            ->where('completed_at', '>=', $since)
            ->count();

        return compact('requested', 'success', 'pending', 'failed');
    }

    protected function queueStats(Business $business): array
    {
        $pendingSales = (int) Sale::query()
            ->where('business_id', $business->id)
            ->where('efris_status', 'pending')
            ->count();

        $driver = config('queue.default', 'sync');
        if ($driver === 'sync') {
            return [
                'status' => $pendingSales > 0 ? 'warn' : 'pass',
                'hint' => 'Queue driver is sync — jobs run inline. For production, use database/redis + queue worker.',
                'detail' => $pendingSales > 0
                    ? $pendingSales . ' sale(s) still marked pending.'
                    : 'No pending EFRIS rows.',
            ];
        }

        $queuedJobs = 0;
        if (Schema::hasTable('jobs')) {
            $queuedJobs = (int) DB::table('jobs')
                ->where('payload', 'like', '%SubmitSaleToEfrisJob%')
                ->count();
        }

        $failedJobs = 0;
        if (Schema::hasTable('failed_jobs')) {
            $failedJobs = (int) DB::table('failed_jobs')
                ->where('payload', 'like', '%SubmitSaleToEfrisJob%')
                ->count();
        }

        if ($failedJobs > 0) {
            return [
                'status' => 'fail',
                'hint' => 'Failed queue jobs — inspect failed_jobs or run php artisan efris:retry-failed.',
                'detail' => $failedJobs . ' failed job(s) in queue; ' . $pendingSales . ' pending sale(s).',
            ];
        }

        if ($queuedJobs > 0 || $pendingSales > 5) {
            return [
                'status' => 'warn',
                'hint' => 'Ensure queue:work is running on the server.',
                'detail' => $queuedJobs . ' queued job(s); ' . $pendingSales . ' pending sale(s).',
            ];
        }

        return [
            'status' => 'pass',
            'hint' => 'Queue driver: ' . $driver . '.',
            'detail' => 'No backlog detected.',
        ];
    }

    protected function check(string $label, bool $ok, string $failHint, ?string $detail = null, ?string $forceStatus = null): array
    {
        $status = $forceStatus ?: ($ok ? 'pass' : 'fail');

        return [
            'label' => $label,
            'status' => $status,
            'detail' => $detail,
            'hint' => $ok && $status === 'pass' ? null : $failHint,
        ];
    }
}
