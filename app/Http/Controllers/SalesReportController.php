<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Support\AnalyticsDateRange;
use App\Support\ReportPeriodResolver;
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
        return view('reports.sales', $this->reportData($request));
    }

    public function print(Request $request)
    {
        return view('reports.sales-print', $this->reportData($request));
    }

    protected function reportData(Request $request): array
    {
        $period = $request->input('period', 'daily');
        $business = $request->user()->business;

        [$start, $end, $label] = ReportPeriodResolver::resolve($period, $request);
        $range = new AnalyticsDateRange($period, $label, $start, $end);

        $salesQuery = Sale::query()
            ->where('business_id', $business->id)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$range->start, $range->end]);

        $summary = (clone $salesQuery)
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as total'))
            ->groupBy('payment_method')
            ->get()
            ->keyBy('payment_method');

        $dailyBreakdown = (clone $salesQuery)
            ->select(DB::raw('DATE(completed_at) as sale_date'), DB::raw('SUM(total) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('sale_date')
            ->orderBy('sale_date')
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
}
