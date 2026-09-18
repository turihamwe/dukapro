<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
class PlatformBusinessMetricsService
{
    public function overview(): array
    {
        $horizons = [
            'daily' => $this->horizonMetrics(
                Carbon::today()->startOfDay(),
                Carbon::today()->endOfDay(),
                Carbon::yesterday()->startOfDay(),
                Carbon::yesterday()->endOfDay(),
                'Today'
            ),
            'weekly' => $this->horizonMetrics(
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek(),
                Carbon::now()->subWeek()->startOfWeek(),
                Carbon::now()->subWeek()->endOfWeek(),
                'This week'
            ),
            'monthly' => $this->horizonMetrics(
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
                Carbon::now()->subMonth()->startOfMonth(),
                Carbon::now()->subMonth()->endOfMonth(),
                'This month'
            ),
            'annual' => $this->horizonMetrics(
                Carbon::now()->startOfYear(),
                Carbon::now()->endOfYear(),
                Carbon::now()->subYear()->startOfYear(),
                Carbon::now()->subYear()->endOfYear(),
                'This year'
            ),
        ];

        return [
            'horizons' => $horizons,
            'active_businesses' => Business::where('is_active', true)->count(),
            'businesses_with_sales' => $this->businessesWithSalesCount(
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ),
            'executive_summary' => $this->buildExecutiveSummary($horizons),
            'focus_horizon' => 'monthly',
        ];
    }

    protected function horizonMetrics(
        Carbon $start,
        Carbon $end,
        Carbon $previousStart,
        Carbon $previousEnd,
        string $label
    ): array {
        $current = $this->metricsForRange($start, $end);
        $previous = $this->metricsForRange($previousStart, $previousEnd);

        return array_merge($current, [
            'label' => $label,
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'previous' => $previous,
            'growth' => [
                'transaction_count' => $this->growthRate($current['transaction_count'], $previous['transaction_count']),
                'revenue' => $this->growthRate($current['revenue'], $previous['revenue']),
                'gross_profit' => $this->growthRate($current['gross_profit'], $previous['gross_profit']),
            ],
        ]);
    }

    protected function metricsForRange(Carbon $start, Carbon $end): array
    {
        $salesQuery = Sale::query()
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$start, $end]);

        $transactionCount = (int) (clone $salesQuery)->count();
        $revenue = round((float) (clone $salesQuery)->sum('total'), 2);
        $grossProfit = $this->grossProfitForRange($start, $end);
        $grossMargin = $revenue > 0 ? round(($grossProfit / $revenue) * 100, 1) : 0.0;

        return [
            'transaction_count' => $transactionCount,
            'revenue' => $revenue,
            'gross_profit' => $grossProfit,
            'gross_margin' => $grossMargin,
        ];
    }

    protected function grossProfitForRange(Carbon $start, Carbon $end): float
    {
        $totals = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.completed_at', [$start, $end])
            ->selectRaw('COALESCE(SUM(sale_items.subtotal), 0) as revenue')
            ->selectRaw('COALESCE(SUM(COALESCE(sale_items.cost_price, 0) * sale_items.quantity), 0) as cost')
            ->first();

        if (! $totals) {
            return 0.0;
        }

        return round(max(0, (float) $totals->revenue - (float) $totals->cost), 2);
    }

    protected function businessesWithSalesCount(Carbon $start, Carbon $end): int
    {
        return (int) Sale::query()
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$start, $end])
            ->distinct('business_id')
            ->count('business_id');
    }

    protected function growthRate(float $current, float $previous): ?float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    public function buildExecutiveSummary(array $horizons): string
    {
        $monthly = $horizons['monthly'] ?? null;
        $weekly = $horizons['weekly'] ?? null;
        $daily = $horizons['daily'] ?? null;

        if (! $monthly) {
            return 'Platform trading data is being collected. Check back once tenants begin completing sales on DukaPro.';
        }

        if ($monthly['transaction_count'] === 0 && ($weekly['transaction_count'] ?? 0) === 0) {
            return 'DukaPro is onboarding businesses across the platform. Once shops start completing sales, this dashboard will highlight volume, revenue velocity, and gross profit for all tenants.';
        }

        $parts = [];

        $parts[] = sprintf(
            'Across DukaPro this month, tenant businesses have completed %s transactions totalling %s in revenue',
            number_format($monthly['transaction_count']),
            $this->formatUgx($monthly['revenue'])
        );

        if ($monthly['gross_profit'] > 0) {
            $parts[] = sprintf(
                'with an aggregate gross profit of %s (%s%% margin)',
                $this->formatUgx($monthly['gross_profit']),
                number_format($monthly['gross_margin'], 1)
            );
        }

        $growthBits = [];

        if ($monthly['growth']['revenue'] !== null) {
            $growthBits[] = $this->growthPhrase('revenue', $monthly['growth']['revenue']);
        }

        if ($monthly['growth']['transaction_count'] !== null) {
            $growthBits[] = $this->growthPhrase('transaction volume', $monthly['growth']['transaction_count']);
        }

        if ($monthly['growth']['gross_profit'] !== null && $monthly['gross_profit'] > 0) {
            $growthBits[] = $this->growthPhrase('gross profit', $monthly['growth']['gross_profit']);
        }

        $sentence = implode(', ', $parts) . '.';

        if ($growthBits !== []) {
            $sentence .= ' Compared with last month, ' . $this->joinNaturalLanguage($growthBits) . '.';
        }

        if ($daily && $daily['transaction_count'] > 0) {
            $sentence .= sprintf(
                ' Today alone, the platform processed %s receipts generating %s.',
                number_format($daily['transaction_count']),
                $this->formatUgx($daily['revenue'])
            );
        }

        if ($weekly && $weekly['transaction_count'] > 0) {
            $sentence .= sprintf(
                ' Weekly velocity stands at %s transactions and %s in revenue — evidence that DukaPro is actively driving day-to-day commerce for its tenants.',
                number_format($weekly['transaction_count']),
                $this->formatUgx($weekly['revenue'])
            );
        }

        return trim($sentence);
    }

    protected function growthPhrase(string $metric, float $percent): string
    {
        $abs = abs($percent);

        if ($abs < 0.1) {
            return "{$metric} held steady";
        }

        if ($percent > 0) {
            return "{$metric} grew {$abs}%";
        }

        return "{$metric} eased {$abs}%";
    }

    protected function joinNaturalLanguage(array $parts): string
    {
        $count = count($parts);

        if ($count === 0) {
            return '';
        }

        if ($count === 1) {
            return $parts[0];
        }

        if ($count === 2) {
            return $parts[0] . ' and ' . $parts[1];
        }

        $last = array_pop($parts);

        return implode(', ', $parts) . ', and ' . $last;
    }

    protected function formatUgx(float $amount): string
    {
        return 'UGX ' . number_format($amount, 0);
    }
}
