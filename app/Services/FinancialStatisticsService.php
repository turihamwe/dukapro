<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Business;
use App\Models\User;
use App\Support\BranchContext;
use App\Support\SupplierCreditMode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinancialStatisticsService
{
    public function forRequest(Business $business, User $user, Request $request): array
    {
        $range = $this->resolveRange(
            (string) $request->input('range', 'day'),
            $request->input('from'),
            $request->input('to')
        );
        $branch = $this->resolveBranch($business, $user, $request);

        return $this->build($business, $range, $branch);
    }

    /**
     * @param  array{key: string, label: string, start: Carbon, end: Carbon, grain: string}  $range
     * @param  array{branch_id: ?int, selected: string, show_picker: bool, branches: \Illuminate\Support\Collection}  $branch
     */
    public function build(Business $business, array $range, array $branch): array
    {
        $buckets = $this->buckets($range['start'], $range['end'], $range['grain']);
        $sales = array_fill_keys(array_keys($buckets), 0.0);
        $purchases = array_fill_keys(array_keys($buckets), 0.0);
        $expenses = array_fill_keys(array_keys($buckets), 0.0);

        $includePurchases = SupplierCreditMode::active($business);

        $this->fill($sales, $this->groupedSales($business->id, $range, $branch['branch_id']));
        if ($includePurchases) {
            $this->fill($purchases, $this->groupedPurchases($business->id, $range, $branch['branch_id']));
        }
        $this->fill($expenses, $this->groupedExpenses($business->id, $range, $branch['branch_id']));

        $salesTotal = round(array_sum($sales), 2);
        $purchaseTotal = $includePurchases ? round(array_sum($purchases), 2) : 0.0;
        $expenseTotal = round(array_sum($expenses), 2);
        $net = round($salesTotal - ($purchaseTotal + $expenseTotal), 2);

        return [
            'range' => $range['key'],
            'label' => $range['label'],
            'from' => $range['start']->toDateString(),
            'to' => $range['end']->toDateString(),
            'branch_id' => $branch['selected'],
            'show_branch_picker' => $branch['show_picker'],
            'branches' => $branch['branches']->map(function (Branch $item) {
                return ['id' => (int) $item->id, 'name' => $item->name];
            })->values()->all(),
            'totals' => [
                'sales' => $salesTotal,
                'purchases' => $purchaseTotal,
                'expenses' => $expenseTotal,
                'net' => $net,
                'sales_formatted' => format_money($salesTotal, $business),
                'purchases_formatted' => format_money($purchaseTotal, $business),
                'expenses_formatted' => format_money($expenseTotal, $business),
                'net_formatted' => format_money($net, $business),
            ],
            'chart' => [
                'labels' => array_values($buckets),
                'sales' => array_map(function ($amount) {
                    return round($amount, 2);
                }, array_values($sales)),
                'purchases' => array_map(function ($amount) {
                    return round($amount, 2);
                }, array_values($purchases)),
                'expenses' => array_map(function ($amount) {
                    return round($amount, 2);
                }, array_values($expenses)),
            ],
            'currency_sample' => format_money(0, $business),
            'include_purchases' => $includePurchases,
        ];
    }

    /**
     * @return array{key: string, label: string, start: Carbon, end: Carbon, grain: string}
     */
    public function resolveRange(string $key, $from, $to): array
    {
        $now = Carbon::now();

        switch ($key) {
            case 'week':
                return [
                    'key' => 'week',
                    'label' => 'This Week',
                    'start' => $now->copy()->startOfWeek(),
                    'end' => $now->copy()->endOfDay(),
                    'grain' => 'day',
                ];
            case 'month':
                return [
                    'key' => 'month',
                    'label' => 'This Month',
                    'start' => $now->copy()->startOfMonth(),
                    'end' => $now->copy()->endOfDay(),
                    'grain' => 'day',
                ];
            case 'year':
                return [
                    'key' => 'year',
                    'label' => 'This Year',
                    'start' => $now->copy()->startOfYear(),
                    'end' => $now->copy()->endOfDay(),
                    'grain' => 'month',
                ];
            case 'custom':
                if (! $from || ! $to) {
                    break;
                }

                try {
                    $start = Carbon::parse($from)->startOfDay();
                    $end = Carbon::parse($to)->endOfDay();
                } catch (\Throwable $exception) {
                    break;
                }

                if ($start->gt($end)) {
                    [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
                }

                $days = $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1;

                return [
                    'key' => 'custom',
                    'label' => $start->format('M j, Y') . ' – ' . $end->format('M j, Y'),
                    'start' => $start,
                    'end' => $end,
                    'grain' => $days > 62 ? 'month' : 'day',
                ];
        }

        return [
            'key' => 'day',
            'label' => 'Today',
            'start' => $now->copy()->startOfDay(),
            'end' => $now->copy()->endOfDay(),
            'grain' => 'hour',
        ];
    }

    /**
     * @return array{branch_id: ?int, selected: string, show_picker: bool, branches: \Illuminate\Support\Collection<int, Branch>}
     */
    protected function resolveBranch(Business $business, User $user, Request $request): array
    {
        $branches = Branch::query()
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name']);

        $lockedId = BranchContext::id();
        if ($lockedId) {
            return [
                'branch_id' => (int) $lockedId,
                'selected' => (string) $lockedId,
                'show_picker' => false,
                'branches' => $branches,
            ];
        }

        if (! $user->isOwner()) {
            $forced = $user->branch_id ? (int) $user->branch_id : null;
            if ($forced === null && $branches->count() > 1) {
                $default = $branches->first();
                $forced = $default ? (int) $default->id : null;
            }

            return [
                'branch_id' => $forced,
                'selected' => $forced ? (string) $forced : 'all',
                'show_picker' => false,
                'branches' => $branches,
            ];
        }

        if ($branches->count() <= 1) {
            return [
                'branch_id' => null,
                'selected' => 'all',
                'show_picker' => false,
                'branches' => $branches,
            ];
        }

        $selected = (string) $request->input('branch_id', 'all');
        if ($selected === '' || $selected === 'all') {
            return [
                'branch_id' => null,
                'selected' => 'all',
                'show_picker' => true,
                'branches' => $branches,
            ];
        }

        $match = $branches->firstWhere('id', (int) $selected);
        if (! $match) {
            return [
                'branch_id' => null,
                'selected' => 'all',
                'show_picker' => true,
                'branches' => $branches,
            ];
        }

        return [
            'branch_id' => (int) $match->id,
            'selected' => (string) $match->id,
            'show_picker' => true,
            'branches' => $branches,
        ];
    }

    /**
     * @param  array<string, float>  $series
     * @param  array<string, float>  $grouped
     */
    protected function fill(array &$series, array $grouped): void
    {
        $keys = array_keys($series);
        $first = $keys[0] ?? null;

        foreach ($grouped as $bucket => $amount) {
            if (array_key_exists($bucket, $series)) {
                $series[$bucket] += $amount;
            } elseif ($first !== null) {
                $series[$first] += $amount;
            }
        }
    }

    /**
     * @param  array{start: Carbon, end: Carbon, grain: string}  $range
     * @return array<string, float>
     */
    protected function groupedSales(int $businessId, array $range, ?int $branchId): array
    {
        $column = 'completed_at';
        $bucket = $this->bucketSql($column, $range['grain']);

        $query = DB::table('sales')
            ->where('business_id', $businessId)
            ->whereNull('deleted_at')
            ->where('status', 'completed')
            ->whereBetween($column, [$range['start'], $range['end']]);

        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }

        return $this->pluckBuckets($query, $bucket, 'total', $range['grain']);
    }

    /**
     * Supplier-credit bills only. Opening balances and historical adjustments are excluded.
     *
     * @param  array{start: Carbon, end: Carbon, grain: string}  $range
     * @return array<string, float>
     */
    protected function groupedPurchases(int $businessId, array $range, ?int $branchId): array
    {
        $dateGrain = $range['grain'] === 'hour' ? 'hour' : $range['grain'];
        $billColumn = $dateGrain === 'hour' ? 'created_at' : 'purchase_date';
        $billBucket = $this->bucketSql($billColumn, $dateGrain);

        $bills = DB::table('supplier_credit_purchases')
            ->where('business_id', $businessId)
            ->whereNull('deleted_at')
            ->where('is_opening_balance', 0)
            ->where('is_historical_adjustment', 0)
            ->whereBetween('purchase_date', [$range['start']->toDateString(), $range['end']->toDateString()]);

        if ($branchId !== null) {
            $bills->where('branch_id', $branchId);
        }

        return $this->pluckBuckets($bills, $billBucket, 'total_amount', $dateGrain);
    }

    /**
     * @param  array{start: Carbon, end: Carbon, grain: string}  $range
     * @return array<string, float>
     */
    protected function groupedExpenses(int $businessId, array $range, ?int $branchId): array
    {
        $column = $range['grain'] === 'hour' ? 'created_at' : 'expense_date';
        $bucket = $this->bucketSql($column, $range['grain']);

        $query = DB::table('expenses')
            ->where('business_id', $businessId)
            ->whereNull('deleted_at')
            ->whereBetween('expense_date', [$range['start']->toDateString(), $range['end']->toDateString()]);

        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }

        return $this->pluckBuckets($query, $bucket, 'amount', $range['grain']);
    }

    /**
     * @return array<string, float>
     */
    protected function pluckBuckets($query, string $bucketSql, string $amountColumn, string $grain): array
    {
        $rows = $query
            ->selectRaw($bucketSql . ' as bucket, COALESCE(SUM(' . $amountColumn . '), 0) as total')
            ->groupBy(DB::raw($bucketSql))
            ->get();

        $totals = [];
        foreach ($rows as $row) {
            $key = $this->normalizeBucket($row->bucket, $grain);
            if ($key === null) {
                continue;
            }
            $totals[$key] = (float) $row->total;
        }

        return $totals;
    }

    protected function normalizeBucket($bucket, string $grain): ?string
    {
        if ($bucket === null) {
            return null;
        }

        $bucket = trim((string) $bucket);
        if ($bucket === '') {
            return null;
        }

        if ($grain === 'month') {
            return substr($bucket, 0, 7);
        }

        if ($grain === 'hour' && preg_match('/^(\d{4}-\d{2}-\d{2})[ T](\d{2})/', $bucket, $match)) {
            return $match[1] . ' ' . $match[2] . ':00';
        }

        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $bucket, $match)) {
            return $match[1];
        }

        return $bucket;
    }

    protected function bucketSql(string $column, string $grain): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            if ($grain === 'hour') {
                return "strftime('%Y-%m-%d %H:00', {$column})";
            }
            if ($grain === 'month') {
                return "strftime('%Y-%m', {$column})";
            }

            return "strftime('%Y-%m-%d', {$column})";
        }

        if ($grain === 'hour') {
            return "DATE_FORMAT({$column}, '%Y-%m-%d %H:00')";
        }
        if ($grain === 'month') {
            return "DATE_FORMAT({$column}, '%Y-%m')";
        }

        return "DATE({$column})";
    }

    /**
     * @return array<string, string>
     */
    protected function buckets(Carbon $start, Carbon $end, string $grain): array
    {
        $items = [];

        if ($grain === 'hour') {
            $cursor = $start->copy()->startOfHour();
            $last = $end->copy();
            if ($last->greaterThan(Carbon::now())) {
                $last = Carbon::now();
            }
            $last = $last->startOfHour();

            while ($cursor->lte($last)) {
                $items[$cursor->format('Y-m-d H:00')] = $cursor->format('g A');
                $cursor->addHour();
            }

            return $items;
        }

        if ($grain === 'month') {
            $cursor = $start->copy()->startOfMonth();
            $last = $end->copy()->startOfMonth();

            while ($cursor->lte($last)) {
                $items[$cursor->format('Y-m')] = $cursor->format('M Y');
                $cursor->addMonth();
            }

            return $items;
        }

        $cursor = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();

        while ($cursor->lte($last)) {
            $items[$cursor->format('Y-m-d')] = $cursor->format('M j');
            $cursor->addDay();
        }

        return $items;
    }
}
