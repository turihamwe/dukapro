<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Support\AnalyticsDateRange;
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
        $period = $request->input('period', 'daily');
        $business = $request->user()->business;

        [$start, $end, $label] = $this->resolvePeriod($period, $request);
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

        return view('reports.sales', compact('period', 'label', 'dailyBreakdown', 'totals', 'range'));
    }

    protected function resolvePeriod(string $period, Request $request): array
    {
        switch ($period) {
            case 'yesterday':
                return [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay(), 'Yesterday'];

            case 'weekly':
                return [Carbon::today()->subDays(6)->startOfDay(), Carbon::today()->endOfDay(), 'Last 7 Days'];

            case 'monthly':
                return [Carbon::today()->subDays(29)->startOfDay(), Carbon::today()->endOfDay(), 'Last 30 Days'];

            case 'yearly':
                return [Carbon::today()->startOfYear()->startOfDay(), Carbon::today()->endOfDay(), 'This Year'];

            case 'custom':
                $from = Carbon::parse($request->input('from', Carbon::today()->toDateString()));
                $to = Carbon::parse($request->input('to', Carbon::today()->toDateString()));
                if ($from->gt($to)) {
                    [$from, $to] = [$to, $from];
                }

                return [$from, $to, $from->format('M j') . ' – ' . $to->format('M j, Y')];

            default:
                return [Carbon::today()->startOfDay(), Carbon::today()->endOfDay(), 'Today'];
        }
    }
}
