<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Damage;
use App\Models\EndOfDayReconciliation;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Support\AnalyticsDateRange;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function ownerOverview(Business $business, AnalyticsDateRange $range): array
    {
        $salesQuery = $this->completedSalesQuery($business, $range);
        $periodSales = (float) (clone $salesQuery)->sum('total');

        $items = SaleItem::query()
            ->whereHas('sale', function ($query) use ($business, $range) {
                $this->applySaleRange($query, $business->id, $range);
            })
            ->with('product:id,cost_price')
            ->get(['id', 'product_id', 'quantity']);

        $costOfGoods = $items->sum(function (SaleItem $item) {
            return $item->quantity * (float) ($item->product->cost_price ?? 0);
        });

        $grossProfit = round($periodSales - $costOfGoods, 2);
        $grossMargin = $periodSales > 0 ? round(($grossProfit / $periodSales) * 100, 1) : 0;

        $products = Product::query()
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->get(['id', 'name', 'sku', 'stock_quantity', 'critical_threshold', 'cost_price', 'measurement_unit']);

        $inventoryValue = round($products->sum(function (Product $product) {
            return $product->stock_quantity * (float) ($product->cost_price ?? 0);
        }), 2);

        $lowStockItems = $products->filter(function (Product $product) {
            $threshold = $product->critical_threshold ?? AnalyticsDateRange::LOW_STOCK_THRESHOLD;

            return $product->stock_quantity <= $threshold;
        });

        $lowStock = $lowStockItems
            ->sortBy('stock_quantity')
            ->values()
            ->take(10);

        $eodReports = EndOfDayReconciliation::query()
            ->with('user:id,name')
            ->where('business_id', $business->id)
            ->whereBetween('reconciliation_date', [$range->start->toDateString(), $range->end->toDateString()])
            ->latest('reconciliation_date')
            ->limit(10)
            ->get();

        return [
            'period_sales' => round($periodSales, 2),
            'gross_profit' => $grossProfit,
            'gross_margin' => $grossMargin,
            'inventory_value' => $inventoryValue,
            'product_count' => $products->count(),
            'low_stock_count' => $lowStockItems->count(),
            'low_stock' => $lowStock,
            'eod_reports' => $eodReports,
            'sale_count' => (clone $salesQuery)->count(),
            'executive' => $this->executiveSummary($business, $range, $salesQuery),
        ];
    }

    public function executiveSummary(Business $business, AnalyticsDateRange $range, $salesQuery = null): array
    {
        $salesQuery = $salesQuery ?? $this->completedSalesQuery($business, $range);

        $cashAvailable = (float) (clone $salesQuery)->where('payment_method', 'cash')->sum('total');
        $mobileAvailable = (float) (clone $salesQuery)->where('payment_method', 'mobile_money')->sum('total');
        $creditSales = (float) (clone $salesQuery)->where('payment_method', 'credit')->sum('total');

        $outstandingDebts = (float) Customer::query()
            ->where('business_id', $business->id)
            ->sum('outstanding_balance');

        $damagesLoss = round(Damage::query()
            ->where('business_id', $business->id)
            ->whereBetween('damage_date', [$range->start->toDateString(), $range->end->toDateString()])
            ->get()
            ->sum(function (Damage $damage) {
                return $damage->lossValue();
            }), 2);

        $overallBalance = round($cashAvailable + $mobileAvailable + $outstandingDebts - $damagesLoss, 2);

        return [
            'cash_available' => round($cashAvailable, 2),
            'mobile_available' => round($mobileAvailable, 2),
            'credit_sales' => round($creditSales, 2),
            'outstanding_debts' => round($outstandingDebts, 2),
            'damages_loss' => $damagesLoss,
            'overall_balance' => $overallBalance,
        ];
    }

    public function ownerSummaryCards(Business $business): array
    {
        $overview = $this->ownerOverview($business, AnalyticsDateRange::today());

        return [
            'inventory_value' => $overview['inventory_value'],
            'todays_sales' => $overview['period_sales'],
            'low_stock_count' => $overview['low_stock_count'],
            'product_count' => $overview['product_count'],
        ];
    }

    public function modernPayload(Business $business): array
    {
        $summary = $this->ownerSummaryCards($business);

        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $range = new AnalyticsDateRange('day', $date->format('D'), $date, $date);
            $last7Days[] = [
                'label' => $date->format('D'),
                'full_label' => $date->format('M j'),
                'value' => round((float) $this->completedSalesQuery($business, $range)->sum('total'), 2),
            ];
        }

        $yesterdayRange = new AnalyticsDateRange('yesterday', 'Yesterday', Carbon::yesterday(), Carbon::yesterday());
        $yesterdaySales = (float) $this->completedSalesQuery($business, $yesterdayRange)->sum('total');
        $todaySales = (float) $summary['todays_sales'];
        $salesChangePct = $yesterdaySales > 0
            ? round((($todaySales - $yesterdaySales) / $yesterdaySales) * 100, 1)
            : ($todaySales > 0 ? 100.0 : 0.0);

        $lastWeekTotal = array_sum(array_column($last7Days, 'value'));
        $inventoryChangePct = $lastWeekTotal > 0 && $summary['inventory_value'] > 0
            ? round(min(12, ($todaySales / max($lastWeekTotal, 1)) * 100), 1)
            : 0.0;

        $productCount = (int) $summary['product_count'];
        $lowStock = (int) $summary['low_stock_count'];
        $availableCount = max(0, $productCount - $lowStock);
        $availablePct = $productCount > 0 ? (int) round(($availableCount / $productCount) * 100) : 100;
        $missingPct = 100 - $availablePct;

        $recentSales = Sale::query()
            ->with(['items.product', 'user'])
            ->where('business_id', $business->id)
            ->orderByDesc('completed_at')
            ->limit(6)
            ->get();

        $notificationCount = min(99, $lowStock + Sale::query()
            ->where('business_id', $business->id)
            ->where('status', '!=', 'completed')
            ->count());

        return [
            'summary' => $summary,
            'last_7_days' => $last7Days,
            'sales_change_pct' => $salesChangePct,
            'inventory_change_pct' => $inventoryChangePct,
            'stock_status' => [
                'available_pct' => $availablePct,
                'missing_pct' => $missingPct,
                'available_count' => $availableCount,
                'low_stock_count' => $lowStock,
            ],
            'recent_sales' => $recentSales,
            'notification_count' => max($notificationCount, $lowStock > 0 ? 1 : 0),
        ];
    }

    public function chartPayload(Business $business, AnalyticsDateRange $range): array
    {
        return [
            'range' => $range->toArray(),
            'stats' => $this->ownerOverview($business, $range),
            'revenue_chart' => $this->revenueChartData($business, $range),
            'stock_chart' => $this->stockChartData($business, $range),
        ];
    }

    protected function revenueChartData(Business $business, AnalyticsDateRange $range): array
    {
        $byPayment = $this->completedSalesQuery($business, $range)
            ->select('payment_method', DB::raw('SUM(total) as revenue'))
            ->groupBy('payment_method')
            ->pluck('revenue', 'payment_method');

        $paymentLabels = [
            'cash' => 'Cash Sales',
            'mobile_money' => 'Mobile Money',
            'credit' => 'Credit Sales',
        ];

        $labels = [];
        $values = [];
        $colors = [];
        $palette = ['#4f46e5', '#059669', '#d97706', '#dc2626', '#0891b2'];

        foreach ($byPayment as $method => $revenue) {
            if ((float) $revenue <= 0) {
                continue;
            }
            $labels[] = $paymentLabels[$method] ?? ucfirst(str_replace('_', ' ', (string) $method));
            $values[] = round((float) $revenue, 2);
        }

        if (empty($labels)) {
            $byCategory = SaleItem::query()
                ->select('measurement_unit', DB::raw('SUM(subtotal) as revenue'))
                ->whereHas('sale', function ($query) use ($business, $range) {
                    $this->applySaleRange($query, $business->id, $range);
                })
                ->groupBy('measurement_unit')
                ->orderByDesc('revenue')
                ->limit(6)
                ->pluck('revenue', 'measurement_unit');

            foreach ($byCategory as $unit => $revenue) {
                if ((float) $revenue <= 0) {
                    continue;
                }
                $labels[] = ucfirst((string) $unit) . ' Category';
                $values[] = round((float) $revenue, 2);
            }
        }

        if (empty($labels)) {
            $labels = ['No Sales'];
            $values = [0];
        }

        foreach ($labels as $index => $label) {
            $colors[] = $palette[$index % count($palette)];
        }

        $total = round(array_sum($values), 2);

        return [
            'labels' => $labels,
            'values' => $values,
            'colors' => $colors,
            'total' => $total,
        ];
    }

    protected function stockChartData(Business $business, AnalyticsDateRange $range): array
    {
        $defaultThreshold = AnalyticsDateRange::LOW_STOCK_THRESHOLD;

        $fastMovers = SaleItem::query()
            ->select('product_id', DB::raw('SUM(quantity) as units_sold'))
            ->whereHas('sale', function ($query) use ($business, $range) {
                $this->applySaleRange($query, $business->id, $range);
            })
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->orderByDesc('units_sold')
            ->limit(12)
            ->get();

        $productIds = $fastMovers->pluck('product_id')->filter()->values();

        if ($productIds->isEmpty()) {
            $products = Product::query()
                ->where('business_id', $business->id)
                ->where('is_active', true)
                ->orderBy('stock_quantity')
                ->limit(10)
                ->get(['id', 'name', 'stock_quantity', 'critical_threshold']);
        } else {
            $products = Product::query()
                ->where('business_id', $business->id)
                ->whereIn('id', $productIds)
                ->get(['id', 'name', 'stock_quantity', 'critical_threshold'])
                ->sortBy(function (Product $product) use ($fastMovers) {
                    $row = $fastMovers->firstWhere('product_id', $product->id);

                    return $row ? (float) $row->units_sold : 0;
                })
                ->reverse()
                ->values();
        }

        $soldMap = $fastMovers->pluck('units_sold', 'product_id');

        $labels = [];
        $stock = [];
        $sold = [];
        $colors = [];
        $thresholds = [];

        foreach ($products as $product) {
            $threshold = (int) ($product->critical_threshold ?? $defaultThreshold);
            $labels[] = $product->name;
            $qty = (float) $product->stock_quantity;
            $stock[] = $qty;
            $sold[] = (float) ($soldMap[$product->id] ?? 0);
            $thresholds[] = $threshold;

            if ($qty <= $threshold) {
                $colors[] = '#dc2626';
            } elseif ($qty <= $threshold * 2) {
                $colors[] = '#f97316';
            } else {
                $colors[] = '#10b981';
            }
        }

        if (empty($labels)) {
            $labels = ['No products'];
            $stock = [0];
            $sold = [0];
            $colors = ['#94a3b8'];
            $thresholds = [$defaultThreshold];
        }

        return [
            'labels' => $labels,
            'stock' => $stock,
            'sold_in_period' => $sold,
            'colors' => $colors,
            'threshold' => $defaultThreshold,
            'thresholds' => $thresholds,
        ];
    }

    protected function completedSalesQuery(Business $business, AnalyticsDateRange $range)
    {
        return Sale::query()
            ->where('business_id', $business->id)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$range->start, $range->end]);
    }

    protected function applySaleRange($query, int $businessId, AnalyticsDateRange $range): void
    {
        $query->where('business_id', $businessId)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$range->start, $range->end]);
    }
}
