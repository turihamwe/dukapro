<?php

namespace App\Services;

use App\Models\Business;
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
            ->get(['id', 'name', 'sku', 'stock_quantity', 'cost_price', 'measurement_unit']);

        $inventoryValue = round($products->sum(function (Product $product) {
            return $product->stock_quantity * (float) ($product->cost_price ?? 0);
        }), 2);

        $lowStock = $products->where('stock_quantity', '<=', AnalyticsDateRange::LOW_STOCK_THRESHOLD)
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
            'low_stock_count' => $lowStock->count(),
            'low_stock' => $lowStock,
            'eod_reports' => $eodReports,
            'sale_count' => (clone $salesQuery)->count(),
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
        $threshold = AnalyticsDateRange::LOW_STOCK_THRESHOLD;

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
                ->get(['id', 'name', 'stock_quantity']);
        } else {
            $products = Product::query()
                ->where('business_id', $business->id)
                ->whereIn('id', $productIds)
                ->get(['id', 'name', 'stock_quantity'])
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

        foreach ($products as $product) {
            $labels[] = $product->name;
            $qty = (float) $product->stock_quantity;
            $stock[] = $qty;
            $sold[] = (float) ($soldMap[$product->id] ?? 0);

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
        }

        return [
            'labels' => $labels,
            'stock' => $stock,
            'sold_in_period' => $sold,
            'colors' => $colors,
            'threshold' => $threshold,
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
