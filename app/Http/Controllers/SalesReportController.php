<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Sale;
use App\Support\AnalyticsDateRange;
use App\Support\ReportPeriodResolver;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view-sales-reports');
    }

    public function index(Request $request)
    {
        return view('reports.sales', $this->summaryData($request));
    }

    public function show(Request $request, Business $business, string $date)
    {
        $day = Carbon::parse($date)->startOfDay();

        return view('reports.sales-day', $this->dayData($request, $day));
    }

    public function print(Request $request)
    {
        return view('reports.sales-print', $this->summaryData($request));
    }

    public function printDay(Request $request, Business $business, string $date)
    {
        $day = Carbon::parse($date)->startOfDay();

        return view('reports.sales-day-print', $this->dayData($request, $day));
    }

    protected function summaryData(Request $request): array
    {
        $period = $request->input('period', 'daily');
        $business = $request->user()->business;

        [$start, $end, $label] = ReportPeriodResolver::resolve($period, $request);
        $range = new AnalyticsDateRange($period, $label, $start, $end);

        $salesQuery = $this->baseSalesQuery($business->id, $range->start, $range->end);

        $summary = (clone $salesQuery)
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as total'))
            ->groupBy('payment_method')
            ->get()
            ->keyBy('payment_method');

        $dailyBreakdown = (clone $salesQuery)
            ->select(DB::raw('DATE(completed_at) as sale_date'), DB::raw('SUM(total) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('sale_date')
            ->orderByDesc('sale_date')
            ->get();

        $totals = [
            'sales_count' => (clone $salesQuery)->count(),
            'sales_total' => (float) (clone $salesQuery)->sum('total'),
            'cash' => (float) ($summary['cash']->total ?? 0),
            'mobile_money' => (float) ($summary['mobile_money']->total ?? 0),
            'credit' => (float) ($summary['credit']->total ?? 0),
            'bank' => (float) ($summary['bank']->total ?? 0),
        ];

        return compact('period', 'label', 'dailyBreakdown', 'totals', 'range');
    }

    protected function dayData(Request $request, Carbon $day): array
    {
        $period = $request->input('period', 'daily');
        $business = $request->user()->business;
        $start = $day->copy()->startOfDay();
        $end = $day->copy()->endOfDay();
        $label = $day->format('l, M j, Y');

        $salesQuery = $this->baseSalesQuery($business->id, $start, $end);

        $summary = (clone $salesQuery)
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as total'))
            ->groupBy('payment_method')
            ->get()
            ->keyBy('payment_method');

        $totals = [
            'sales_count' => (clone $salesQuery)->count(),
            'sales_total' => (float) (clone $salesQuery)->sum('total'),
            'cash' => (float) ($summary['cash']->total ?? 0),
            'mobile_money' => (float) ($summary['mobile_money']->total ?? 0),
            'credit' => (float) ($summary['credit']->total ?? 0),
            'bank' => (float) ($summary['bank']->total ?? 0),
        ];

        $sales = (clone $salesQuery)
            ->with(['user:id,name,role', 'items:id,sale_id,product_name,quantity,unit_price,subtotal'])
            ->orderByDesc('completed_at')
            ->get(['id', 'sale_number', 'user_id', 'total', 'payment_method', 'completed_at']);

        $productSummary = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.business_id', $business->id)
            ->where('sales.status', 'completed')
            ->whereBetween('sales.completed_at', [$start, $end])
            ->select(
                'sale_items.product_name',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.subtotal) as total_revenue'),
                DB::raw('COUNT(DISTINCT sales.id) as sale_count')
            )
            ->groupBy('sale_items.product_name')
            ->orderByDesc('total_revenue')
            ->get();

        return [
            'period' => $period,
            'label' => $label,
            'date' => $day->toDateString(),
            'totals' => $totals,
            'sales' => $sales,
            'productSummary' => $productSummary,
        ];
    }

    protected function baseSalesQuery(int $businessId, Carbon $start, Carbon $end)
    {
        return Sale::query()
            ->where('business_id', $businessId)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$start, $end]);
    }
}
