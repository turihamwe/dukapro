<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\SubscriptionPayment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class BusinessSalesMetricsService
{
    public function overview(?int $fromYear = null): array
    {
        $fromYear = $this->normalizeFromYear($fromYear);
        $funnel = $this->funnel();
        $horizons = $this->horizonProjections();

        return [
            'funnel' => $funnel,
            'horizons' => $horizons,
            'trend_charts' => $this->funnelTrendCharts($fromYear),
            'from_year' => $fromYear,
            'from_year_options' => $this->fromYearOptions(),
            'projections_start' => $this->projectionsStartAt()->toDateString(),
            'mrr' => $this->monthlyRecurringRevenue(),
            'executive_summary' => $this->buildExecutiveSummary($funnel, $horizons),
            'focus_horizon' => 'monthly',
        ];
    }

    public function fromYearOptions(): array
    {
        $currentYear = (int) now()->year;

        return range(2026, max(2026, $currentYear));
    }

    public function normalizeFromYear(?int $fromYear): int
    {
        $fromYear = (int) ($fromYear ?? 2026);
        $currentYear = (int) now()->year;

        return max(2026, min($fromYear, max(2026, $currentYear)));
    }

    public function funnelTrendCharts(int $fromYear): array
    {
        $fromYear = $this->normalizeFromYear($fromYear);
        $milestones = $this->loadFunnelMilestones();

        return [
            'daily' => $this->buildTrendSeries($milestones, 'daily', $fromYear),
            'weekly' => $this->buildTrendSeries($milestones, 'weekly', $fromYear),
            'monthly' => $this->buildTrendSeries($milestones, 'monthly', $fromYear),
            'annual' => $this->buildTrendSeries($milestones, 'annual', $fromYear),
        ];
    }

    public function funnel(): array
    {
        $registered = $this->registeredQuery()->count();
        $catalog = $this->catalogQuery()->count();
        $subscribed = $this->subscribedQuery()->count();

        return [
            'registered' => [
                'key' => 'registered',
                'label' => 'Registered Businesses',
                'description' => 'Total business signups on DukaPro',
                'count' => $registered,
                'view_route' => route('superadmin.business-sales.businesses', ['stage' => 'registered']),
            ],
            'catalog' => [
                'key' => 'catalog',
                'label' => 'Active Catalog Businesses',
                'description' => 'Businesses with at least one product in catalog',
                'count' => $catalog,
                'view_route' => route('superadmin.business-sales.businesses', ['stage' => 'catalog']),
            ],
            'subscribed' => [
                'key' => 'subscribed',
                'label' => 'Subscribed Businesses',
                'description' => 'Businesses on an active paid subscription',
                'count' => $subscribed,
                'view_route' => route('superadmin.business-sales.businesses', ['stage' => 'subscribed']),
            ],
            'conversions' => [
                'registered_to_catalog' => $this->conversionRate($catalog, $registered),
                'catalog_to_subscribed' => $this->conversionRate($subscribed, $catalog),
                'registered_to_subscribed' => $this->conversionRate($subscribed, $registered),
            ],
        ];
    }

    public function projectionsStartAt(): Carbon
    {
        return Carbon::create(2026, 9, 1)->startOfDay();
    }

    public function horizonProjections(): array
    {
        $anchor = $this->projectionsStartAt();
        $now = Carbon::now();

        $todayStart = $this->clampRangeStart(Carbon::today()->startOfDay());
        $todayEnd = Carbon::today()->endOfDay();
        $yesterdayStart = $this->clampRangeStart(Carbon::yesterday()->startOfDay());
        $yesterdayEnd = Carbon::yesterday()->endOfDay();

        $weekStart = $this->clampRangeStart(Carbon::now()->startOfWeek());
        $weekEnd = Carbon::now()->endOfWeek();
        if ($weekEnd->gt($now)) {
            $weekEnd = $now->copy();
        }
        $prevWeekStart = $this->clampRangeStart(Carbon::now()->subWeek()->startOfWeek());
        $prevWeekEnd = Carbon::now()->subWeek()->endOfWeek();

        $monthStart = $this->clampRangeStart(Carbon::now()->startOfMonth());
        $monthEnd = Carbon::now()->endOfMonth();
        if ($monthEnd->gt($now)) {
            $monthEnd = $now->copy();
        }
        $prevMonthStart = $this->clampRangeStart(Carbon::now()->subMonth()->startOfMonth());
        $prevMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        return [
            'daily' => $this->horizonMetrics(
                $todayStart,
                $todayEnd,
                $yesterdayStart,
                $yesterdayEnd,
                'Today',
                max(1, $todayStart->diffInDays($now) + 1)
            ),
            'weekly' => $this->horizonMetrics(
                $weekStart,
                $weekEnd,
                $prevWeekStart,
                $prevWeekEnd,
                'This week',
                max(1, $weekStart->diffInDays($now) + 1)
            ),
            'monthly' => $this->horizonMetrics(
                $monthStart,
                $monthEnd,
                $prevMonthStart,
                $prevMonthEnd,
                'This month',
                max(1, $monthStart->diffInDays($now) + 1)
            ),
            'annual' => $this->horizonMetrics(
                $anchor->copy(),
                $now->copy(),
                $anchor->copy(),
                $anchor->copy()->subDay()->endOfDay(),
                'Since 1 Sep 2026',
                max(1, $anchor->diffInDays($now) + 1)
            ),
        ];
    }

    public function businessesQuery(string $stage, ?string $period = null, ?string $search = null): Builder
    {
        if ($stage === 'catalog') {
            $query = $this->catalogQuery();
        } elseif ($stage === 'subscribed') {
            $query = $this->subscribedQuery();
        } else {
            $query = $this->registeredQuery();
        }

        if ($period && $period !== 'all') {
            [$start, $end] = $this->periodBounds($period);

            if ($stage === 'registered') {
                $query->whereBetween('created_at', [$start, $end]);
            } elseif ($stage === 'catalog') {
                $query->whereHas('products', fn (Builder $q) => $q->whereBetween('created_at', [$start, $end]));
            } else {
                $query = Business::query()
                    ->whereHas('subscriptionPayments', fn (Builder $q) => $q
                        ->where('status', 'completed')
                        ->whereBetween('paid_at', [$start, $end]));
            }
        }

        if ($search) {
            $term = '%' . trim($search) . '%';
            $query->where(function (Builder $q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term);
            });
        }

        return $query->withCount('products')->orderByDesc('created_at');
    }

    public function stageLabel(string $stage): string
    {
        if ($stage === 'catalog') {
            return 'Active Catalog Businesses';
        }

        if ($stage === 'subscribed') {
            return 'Subscribed Businesses';
        }

        return 'Registered Businesses';
    }

    public function periodLabel(?string $period): ?string
    {
        if (! $period || $period === 'all') {
            return null;
        }

        $labels = [
            'daily' => 'Today',
            'weekly' => 'This week',
            'monthly' => 'This month',
            'annual' => 'This year',
        ];

        return $labels[$period] ?? ucfirst($period);
    }

    protected function horizonMetrics(
        Carbon $start,
        Carbon $end,
        Carbon $previousStart,
        Carbon $previousEnd,
        string $label,
        int $elapsedDays
    ): array {
        $current = $this->velocityForRange($start, $end);
        $previous = $this->velocityForRange($previousStart, $previousEnd);
        $elapsedDays = max(1, $elapsedDays);

        $dailyRate = [
            'registrations' => $current['registrations'] / $elapsedDays,
            'catalog' => $current['catalog'] / $elapsedDays,
            'subscriptions' => $current['subscriptions'] / $elapsedDays,
            'subscription_revenue' => $current['subscription_revenue'] / $elapsedDays,
        ];

        return array_merge($current, [
            'label' => $label,
            'period_key' => strtolower(str_replace(' ', '_', $label)),
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'previous' => $previous,
            'growth' => [
                'registrations' => $this->growthRate($current['registrations'], $previous['registrations']),
                'catalog' => $this->growthRate($current['catalog'], $previous['catalog']),
                'subscriptions' => $this->growthRate($current['subscriptions'], $previous['subscriptions']),
                'subscription_revenue' => $this->growthRate($current['subscription_revenue'], $previous['subscription_revenue']),
            ],
            'projections' => [
                'registrations' => round($dailyRate['registrations'] * 30, 1),
                'catalog' => round($dailyRate['catalog'] * 30, 1),
                'subscriptions' => round($dailyRate['subscriptions'] * 30, 1),
                'subscription_revenue' => round($dailyRate['subscription_revenue'] * 30, 0),
                'weekly_registrations' => round($dailyRate['registrations'] * 7, 1),
                'annual_registrations' => round($dailyRate['registrations'] * 365, 0),
                'annual_subscriptions' => round($dailyRate['subscriptions'] * 365, 0),
                'annual_revenue' => round($dailyRate['subscription_revenue'] * 365, 0),
            ],
            'view_routes' => [
                'registrations' => route('superadmin.business-sales.businesses', [
                    'stage' => 'registered',
                    'period' => $this->horizonPeriodKey($label),
                ]),
                'catalog' => route('superadmin.business-sales.businesses', [
                    'stage' => 'catalog',
                    'period' => $this->horizonPeriodKey($label),
                ]),
                'subscriptions' => route('superadmin.business-sales.businesses', [
                    'stage' => 'subscribed',
                    'period' => $this->horizonPeriodKey($label),
                ]),
            ],
        ]);
    }

    protected function clampRangeStart(Carbon $start): Carbon
    {
        $anchor = $this->projectionsStartAt();

        return $start->lt($anchor) ? $anchor->copy() : $start->copy();
    }

    protected function velocityForRange(Carbon $start, Carbon $end): array
    {
        $start = $this->clampRangeStart($start);

        if ($end->lt($start)) {
            return [
                'registrations' => 0,
                'catalog' => 0,
                'subscriptions' => 0,
                'subscription_revenue' => 0,
            ];
        }

        $registrations = Business::query()
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $catalog = Business::query()
            ->whereHas('products', fn (Builder $q) => $q->whereBetween('created_at', [$start, $end]))
            ->count();

        $subscriptions = Business::query()
            ->whereHas('subscriptionPayments', fn (Builder $q) => $q
                ->where('status', 'completed')
                ->whereBetween('paid_at', [$start, $end]))
            ->count();

        $subscriptionRevenue = (float) SubscriptionPayment::query()
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$start, $end])
            ->sum('amount');

        return [
            'registrations' => $registrations,
            'catalog' => $catalog,
            'subscriptions' => $subscriptions,
            'subscription_revenue' => round($subscriptionRevenue, 0),
        ];
    }

    protected function registeredQuery(): Builder
    {
        return Business::query();
    }

    protected function catalogQuery(): Builder
    {
        return Business::query()->whereHas('products');
    }

    protected function subscribedQuery(): Builder
    {
        return Business::query()
            ->where('subscription_status', SubscriptionStatus::ACTIVE)
            ->where(function (Builder $q) {
                $q->whereNull('subscription_ends_at')
                    ->orWhere('subscription_ends_at', '>=', now());
            });
    }

    protected function monthlyRecurringRevenue(): float
    {
        $monthlyAmount = (float) config('subscription.plans.monthly.amount', 100000);

        return round($this->subscribedQuery()->count() * $monthlyAmount, 0);
    }

    protected function periodBounds(string $period): array
    {
        $now = Carbon::now();

        if ($period === 'weekly') {
            $end = Carbon::now()->endOfWeek();
            if ($end->gt($now)) {
                $end = $now->copy();
            }

            return [$this->clampRangeStart(Carbon::now()->startOfWeek()), $end];
        }

        if ($period === 'monthly') {
            $end = Carbon::now()->endOfMonth();
            if ($end->gt($now)) {
                $end = $now->copy();
            }

            return [$this->clampRangeStart(Carbon::now()->startOfMonth()), $end];
        }

        if ($period === 'annual') {
            return [$this->projectionsStartAt(), $now->copy()];
        }

        return [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()];
    }

    protected function horizonPeriodKey(string $label): string
    {
        $keys = [
            'Today' => 'daily',
            'This week' => 'weekly',
            'This month' => 'monthly',
            'This year' => 'annual',
            'Since 1 Sep 2026' => 'annual',
        ];

        return $keys[$label] ?? 'daily';
    }

    protected function conversionRate(int $numerator, int $denominator): float
    {
        if ($denominator <= 0) {
            return 0.0;
        }

        return round(($numerator / $denominator) * 100, 1);
    }

    protected function growthRate(float $current, float $previous): ?float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    protected function buildExecutiveSummary(array $funnel, array $horizons): string
    {
        $registered = $funnel['registered']['count'];
        $catalog = $funnel['catalog']['count'];
        $subscribed = $funnel['subscribed']['count'];
        $conversions = $funnel['conversions'];

        if ($registered === 0) {
            return 'No businesses have registered yet. Once signups begin, this dashboard will track onboarding, catalog activation, and subscription conversion across DukaPro.';
        }

        $monthly = $horizons['monthly'] ?? null;
        $parts = [
            sprintf(
                'DukaPro has %s registered businesses, %s with an active product catalog (%s%% signup-to-catalog), and %s on paid subscriptions (%s%% catalog-to-paid)',
                number_format($registered),
                number_format($catalog),
                number_format($conversions['registered_to_catalog'], 1),
                number_format($subscribed),
                number_format($conversions['catalog_to_subscribed'], 1)
            ),
        ];

        $parts[] = sprintf(
            'Overall signup-to-subscription conversion stands at %s%% with estimated MRR of %s',
            number_format($conversions['registered_to_subscribed'], 1),
            $this->formatUgx($this->monthlyRecurringRevenue())
        );

        if ($monthly) {
            $parts[] = sprintf(
                'This month: %s new signups, %s catalog activations, and %s new subscription payments totalling %s',
                number_format($monthly['registrations']),
                number_format($monthly['catalog']),
                number_format($monthly['subscriptions']),
                $this->formatUgx($monthly['subscription_revenue'])
            );

            if ($monthly['growth']['registrations'] !== null) {
                $parts[] = $this->growthPhrase('New signups', $monthly['growth']['registrations']) . ' versus last month';
            }
        }

        return implode('. ', $parts) . '.';
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

    protected function formatUgx(float $amount): string
    {
        return 'UGX ' . number_format($amount, 0);
    }

    protected function loadFunnelMilestones(): array
    {
        $registered = Business::query()
            ->orderBy('created_at')
            ->pluck('created_at')
            ->map(function ($date) {
                return Carbon::parse($date);
            })
            ->values()
            ->all();

        $catalog = DB::table('products')
            ->select('business_id', DB::raw('MIN(created_at) as first_product_at'))
            ->groupBy('business_id')
            ->orderBy('first_product_at')
            ->pluck('first_product_at')
            ->map(function ($date) {
                return Carbon::parse($date);
            })
            ->values()
            ->all();

        $paymentsByBusiness = SubscriptionPayment::query()
            ->where('status', 'completed')
            ->whereNotNull('paid_at')
            ->orderBy('paid_at')
            ->get(['business_id', 'paid_at', 'metadata'])
            ->groupBy('business_id');

        return [
            'registered' => $registered,
            'catalog' => $catalog,
            'payments_by_business' => $paymentsByBusiness,
        ];
    }

    protected function buildTrendSeries(array $milestones, string $granularity, int $fromYear): array
    {
        $buckets = $this->generateBuckets($granularity, $fromYear);
        $registeredTimes = $milestones['registered'];
        $catalogTimes = $milestones['catalog'];
        $paymentsByBusiness = $milestones['payments_by_business'];

        $registered = [];
        $catalog = [];
        $subscribed = [];
        $labels = [];

        $regIdx = 0;
        $catIdx = 0;
        $regTotal = count($registeredTimes);
        $catTotal = count($catalogTimes);

        foreach ($buckets as $bucket) {
            $labels[] = $bucket['label'];
            $end = $bucket['end'];

            while ($regIdx < $regTotal && $registeredTimes[$regIdx]->lte($end)) {
                $regIdx++;
            }

            while ($catIdx < $catTotal && $catalogTimes[$catIdx]->lte($end)) {
                $catIdx++;
            }

            $registered[] = $regIdx;
            $catalog[] = $catIdx;
            $subscribed[] = $this->activeSubscribedCountAt($end, $paymentsByBusiness);
        }

        return [
            'label' => $this->trendGranularityLabel($granularity, $fromYear),
            'labels' => $labels,
            'registered' => $registered,
            'catalog' => $catalog,
            'subscribed' => $subscribed,
        ];
    }

    protected function generateBuckets(string $granularity, int $fromYear): array
    {
        $rangeStart = Carbon::create($fromYear, 1, 1)->startOfDay();
        $now = Carbon::now();

        if ($rangeStart->gt($now)) {
            $rangeStart = $now->copy()->startOfDay();
        }

        $buckets = [];

        if ($granularity === 'daily') {
            $cursor = $rangeStart->copy();

            while ($cursor->lte(Carbon::today())) {
                $buckets[] = [
                    'label' => $cursor->format('M j'),
                    'end' => $cursor->copy()->endOfDay(),
                ];
                $cursor->addDay();
            }

            return $buckets;
        }

        if ($granularity === 'weekly') {
            $cursor = $rangeStart->copy()->startOfWeek();

            while ($cursor->lte($now)) {
                $weekEnd = $cursor->copy()->endOfWeek();
                if ($weekEnd->gt($now)) {
                    $weekEnd = $now->copy();
                }

                $buckets[] = [
                    'label' => $cursor->format('M j'),
                    'end' => $weekEnd,
                ];
                $cursor->addWeek();
            }

            return $buckets;
        }

        if ($granularity === 'monthly') {
            $cursor = $rangeStart->copy()->startOfMonth();

            while ($cursor->lte($now->copy()->startOfMonth())) {
                $monthEnd = $cursor->copy()->endOfMonth();
                if ($monthEnd->gt($now)) {
                    $monthEnd = $now->copy();
                }

                $buckets[] = [
                    'label' => $cursor->format('M Y'),
                    'end' => $monthEnd,
                ];
                $cursor->addMonth();
            }

            return $buckets;
        }

        for ($year = $fromYear; $year <= (int) $now->year; $year++) {
            $yearStart = Carbon::create($year, 1, 1)->startOfDay();
            $yearEnd = $yearStart->copy()->endOfYear();
            if ($yearEnd->gt($now)) {
                $yearEnd = $now->copy();
            }

            $buckets[] = [
                'label' => (string) $year,
                'end' => $yearEnd,
            ];
        }

        return $buckets;
    }

    protected function trendGranularityLabel(string $granularity, int $fromYear): string
    {
        $suffix = ' from ' . $fromYear;

        $labels = [
            'daily' => 'Daily' . $suffix,
            'weekly' => 'Weekly' . $suffix,
            'monthly' => 'Monthly' . $suffix,
            'annual' => 'Annual' . $suffix,
        ];

        return $labels[$granularity] ?? ucfirst($granularity) . $suffix;
    }

    protected function activeSubscribedCountAt(Carbon $at, $paymentsByBusiness): int
    {
        $count = 0;

        foreach ($paymentsByBusiness as $payments) {
            $endsAt = null;

            foreach ($payments as $payment) {
                $paidAt = Carbon::parse($payment->paid_at);

                if ($paidAt->gt($at)) {
                    break;
                }

                $days = $this->subscriptionDaysFromPayment($payment);
                $startsFrom = ($endsAt && $endsAt->gt($paidAt)) ? $endsAt->copy() : $paidAt->copy();
                $endsAt = $startsFrom->copy()->addDays($days);
            }

            if ($endsAt && $endsAt->gt($at)) {
                $count++;
            }
        }

        return $count;
    }

    protected function subscriptionDaysFromPayment(SubscriptionPayment $payment): int
    {
        $metadata = $payment->metadata ?? [];
        $days = isset($metadata['days']) ? (int) $metadata['days'] : 0;

        if ($days > 0) {
            return $days;
        }

        $planKey = $metadata['plan'] ?? config('subscription.default_plan', 'monthly');

        return (int) config('subscription.plans.' . $planKey . '.days', 30);
    }
}
