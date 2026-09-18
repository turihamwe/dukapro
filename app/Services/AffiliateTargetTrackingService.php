<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\AffiliateReferral;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AffiliateTargetTrackingService
{
    public function defaultDailyTarget(): float
    {
        return (float) config('affiliates.default_daily_shop_target', 1);
    }

    public function resolveDailyTarget(?Affiliate $affiliate): float
    {
        if (! $affiliate || $affiliate->daily_shop_target === null) {
            return $this->defaultDailyTarget();
        }

        return max(0, (float) $affiliate->daily_shop_target);
    }

    public function projections(float $dailyTarget): array
    {
        return [
            'daily' => round($dailyTarget, 2),
            'weekly' => round($dailyTarget * 7, 2),
            'monthly' => round($dailyTarget * 30, 2),
            'annual' => round($dailyTarget * 365, 2),
        ];
    }

    public function progress(float $actual, float $target): array
    {
        if ($target <= 0) {
            return [
                'actual' => $actual,
                'target' => $target,
                'percent' => 100.0,
                'status' => 'green',
                'label' => 'No target set',
            ];
        }

        $percent = min(100, round(($actual / $target) * 100, 1));
        $ratio = $actual / $target;

        if ($ratio >= 1) {
            $status = 'green';
            $label = 'On track';
        } elseif ($ratio >= 0.5) {
            $status = 'yellow';
            $label = 'Behind';
        } else {
            $status = 'red';
            $label = 'At risk';
        }

        return [
            'actual' => $actual,
            'target' => $target,
            'percent' => $percent,
            'status' => $status,
            'label' => $label,
        ];
    }

    public function referralCounts(int $affiliateId): array
    {
        $batch = $this->batchReferralCounts(collect([$affiliateId]));

        return $batch[$affiliateId] ?? [
            'daily' => 0,
            'weekly' => 0,
            'monthly' => 0,
            'annual' => 0,
        ];
    }

    public function batchReferralCounts(Collection $affiliateIds): array
    {
        $affiliateIds = $affiliateIds->filter()->unique()->values();

        if ($affiliateIds->isEmpty()) {
            return [];
        }

        $today = Carbon::today()->toDateString();
        $weekStart = Carbon::now()->startOfWeek()->toDateTimeString();
        $weekEnd = Carbon::now()->endOfWeek()->toDateTimeString();
        $monthStart = Carbon::now()->startOfMonth()->toDateTimeString();
        $monthEnd = Carbon::now()->endOfMonth()->toDateTimeString();
        $yearStart = Carbon::now()->startOfYear()->toDateTimeString();
        $yearEnd = Carbon::now()->endOfYear()->toDateTimeString();

        $rows = AffiliateReferral::query()
            ->select('affiliate_id')
            ->selectRaw('SUM(CASE WHEN DATE(referred_at) = ? THEN 1 ELSE 0 END) as daily_count', [$today])
            ->selectRaw('SUM(CASE WHEN referred_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as weekly_count', [$weekStart, $weekEnd])
            ->selectRaw('SUM(CASE WHEN referred_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as monthly_count', [$monthStart, $monthEnd])
            ->selectRaw('SUM(CASE WHEN referred_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as annual_count', [$yearStart, $yearEnd])
            ->whereIn('affiliate_id', $affiliateIds)
            ->groupBy('affiliate_id')
            ->get();

        $mapped = [];

        foreach ($affiliateIds as $affiliateId) {
            $mapped[$affiliateId] = [
                'daily' => 0,
                'weekly' => 0,
                'monthly' => 0,
                'annual' => 0,
            ];
        }

        foreach ($rows as $row) {
            $mapped[(int) $row->affiliate_id] = [
                'daily' => (int) $row->daily_count,
                'weekly' => (int) $row->weekly_count,
                'monthly' => (int) $row->monthly_count,
                'annual' => (int) $row->annual_count,
            ];
        }

        return $mapped;
    }

    public function trackingPayload(Affiliate $affiliate, ?array $actuals = null): array
    {
        $dailyTarget = $this->resolveDailyTarget($affiliate);
        $projections = $this->projections($dailyTarget);
        $actuals = $actuals ?? $this->referralCounts((int) $affiliate->id);

        $horizons = [];

        foreach (['daily', 'weekly', 'monthly', 'annual'] as $horizon) {
            $horizons[$horizon] = $this->progress(
                (float) ($actuals[$horizon] ?? 0),
                (float) ($projections[$horizon] ?? 0)
            );
        }

        return [
            'daily_target' => $dailyTarget,
            'projections' => $projections,
            'actuals' => $actuals,
            'horizons' => $horizons,
        ];
    }

    public function projectionSummary(?float $dailyTarget = null): array
    {
        $dailyTarget = $dailyTarget ?? $this->defaultDailyTarget();
        $projections = $this->projections($dailyTarget);

        return [
            'daily_target' => $dailyTarget,
            'projections' => $projections,
            'copy' => sprintf(
                'At %s shop(s) per day → %s/week · %s/month · %s/year',
                $this->formatTarget($dailyTarget),
                $this->formatTarget($projections['weekly']),
                $this->formatTarget($projections['monthly']),
                $this->formatTarget($projections['annual'])
            ),
        ];
    }

    protected function formatTarget(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
