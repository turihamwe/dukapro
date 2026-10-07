<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Sale;
use App\Scopes\BranchScope;
use App\Support\AnalyticsDateRange;
use App\Support\ReportPeriodResolver;
use App\Support\ReportShareMessages;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

        $branchMeta = $this->resolveReportBranch($request, $business);
        $salesQuery = $this->baseSalesQuery($business->id, $range->start, $range->end, $branchMeta['branchId']);

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

        $shareMessage = ReportShareMessages::salesSummary(
            $business,
            $label,
            $totals,
            $branchMeta['branchName'] ?? null
        );
        $shareSubject = 'Sales report — ' . $label . ' — ' . $business->name;

        return array_merge(
            compact('period', 'label', 'dailyBreakdown', 'totals', 'range', 'business'),
            $branchMeta,
            [
                'shareMessage' => $shareMessage,
                'shareWhatsAppUrl' => whatsapp_share_url(null, $shareMessage),
                'shareEmailUrl' => mailto_share_url($business->email, $shareSubject, $shareMessage),
            ]
        );
    }

    protected function dayData(Request $request, Carbon $day): array
    {
        $period = $request->input('period', 'daily');
        $business = $request->user()->business;
        $start = $day->copy()->startOfDay();
        $end = $day->copy()->endOfDay();
        $label = $day->format('l, M j, Y');

        $branchMeta = $this->resolveReportBranch($request, $business);
        $branchId = $branchMeta['branchId'];
        $salesQuery = $this->baseSalesQuery($business->id, $start, $end, $branchId);

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

        $productSummaryQuery = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.business_id', $business->id)
            ->where('sales.status', 'completed')
            ->whereBetween('sales.completed_at', [$start, $end]);

        if ($branchId !== null) {
            $productSummaryQuery->where('sales.branch_id', $branchId);
        }

        $productSummary = $productSummaryQuery->select(
                'sale_items.product_name',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.subtotal) as total_revenue'),
                DB::raw('COUNT(DISTINCT sales.id) as sale_count')
            )
            ->groupBy('sale_items.product_name')
            ->orderByDesc('total_revenue')
            ->get();

        $shareMessage = ReportShareMessages::salesDay(
            $business,
            $label,
            $totals,
            $branchMeta['branchName'] ?? null
        );
        $shareSubject = 'Daily sales — ' . $label . ' — ' . $business->name;

        return array_merge([
            'period' => $period,
            'label' => $label,
            'date' => $day->toDateString(),
            'totals' => $totals,
            'sales' => $sales,
            'productSummary' => $productSummary,
            'business' => $business,
            'shareMessage' => $shareMessage,
            'shareWhatsAppUrl' => whatsapp_share_url(null, $shareMessage),
            'shareEmailUrl' => mailto_share_url($business->email, $shareSubject, $shareMessage),
        ], $branchMeta);
    }

    protected function baseSalesQuery(int $businessId, Carbon $start, Carbon $end, ?int $branchId = null)
    {
        $query = Sale::query()
            ->where('business_id', $businessId)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$start, $end]);

        if ($branchId !== null) {
            $query->withoutGlobalScope(BranchScope::class)
                ->where('sales.branch_id', $branchId);
        }

        return $query;
    }

    /**
     * @return array{
     *     branchId: ?int,
     *     branchName: ?string,
     *     branches: \Illuminate\Support\Collection<int, string>,
     *     showBranchPicker: bool,
     *     branchQuery: array<string, int>
     * }
     */
    protected function resolveReportBranch(Request $request, Business $business): array
    {
        $user = $request->user();
        $branches = $this->activeBranches($business->id);

        if ($user->branch_id) {
            $branch = $branches->firstWhere('id', (int) $user->branch_id)
                ?? Branch::query()->find($user->branch_id);

            return [
                'branchId' => (int) $user->branch_id,
                'branchName' => $branch ? $branch->name : null,
                'branches' => collect(),
                'showBranchPicker' => false,
                'branchQuery' => ['branch_id' => (int) $user->branch_id],
            ];
        }

        if ($branches->count() <= 1) {
            $only = $branches->first();
            $branchId = $only ? (int) $only->id : null;

            return [
                'branchId' => $branchId,
                'branchName' => $only ? $only->name : null,
                'branches' => collect(),
                'showBranchPicker' => false,
                'branchQuery' => $branchId ? ['branch_id' => $branchId] : [],
            ];
        }

        if (! $user->isOwner()) {
            $default = $this->defaultBranch($branches);
            $branchId = $default ? (int) $default->id : null;

            return [
                'branchId' => $branchId,
                'branchName' => $default ? $default->name : null,
                'branches' => collect(),
                'showBranchPicker' => false,
                'branchQuery' => $branchId ? ['branch_id' => $branchId] : [],
            ];
        }

        $selectedId = $request->input('branch_id');
        $branch = null;
        if ($selectedId !== null && $selectedId !== '') {
            $branch = $branches->firstWhere('id', (int) $selectedId);
        }
        if (! $branch) {
            $branch = $this->defaultBranch($branches);
        }

        $branchId = $branch ? (int) $branch->id : null;

        return [
            'branchId' => $branchId,
            'branchName' => $branch ? $branch->name : null,
            'branches' => $branches->pluck('name', 'id'),
            'showBranchPicker' => true,
            'branchQuery' => $branchId ? ['branch_id' => $branchId] : [],
        ];
    }

    protected function activeBranches(int $businessId): Collection
    {
        return Branch::query()
            ->where('business_id', $businessId)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'is_default']);
    }

    protected function defaultBranch(Collection $branches): ?Branch
    {
        if ($branches->isEmpty()) {
            return null;
        }

        return $branches->firstWhere('is_default', true) ?? $branches->first();
    }
}
