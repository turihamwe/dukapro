<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Services\AffiliatePerformanceService;
use Illuminate\Http\Request;

class AffiliateController extends Controller
{
    protected AffiliatePerformanceService $performanceService;

    public function __construct(AffiliatePerformanceService $performanceService)
    {
        $this->performanceService = $performanceService;
    }

    public function index(Request $request)
    {
        $filters = [
            'period' => $request->query('period', 'all'),
            'search' => $request->query('search', ''),
            'status' => $request->query('status', 'all'),
        ];

        $summary = $this->performanceService->platformSummary($filters);
        $rows = $this->performanceService->affiliateRows($filters);

        return view('superadmin.affiliates.index', compact('summary', 'rows', 'filters'));
    }

    public function show(Request $request, Affiliate $affiliate)
    {
        $range = $this->performanceService->dateRange($request->query('period'));

        if ($request->expectsJson()) {
            return response()->json(
                $this->performanceService->affiliateBreakdown($affiliate, $range)
            );
        }

        return redirect()->route('superadmin.affiliates.index', $request->only(['period', 'search', 'status']));
    }
}
