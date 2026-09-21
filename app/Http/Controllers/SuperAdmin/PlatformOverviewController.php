<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\AffiliatePerformanceService;
use App\Services\AffiliateTargetTrackingService;
use App\Services\PlatformBusinessMetricsService;
use Illuminate\Http\Request;

class PlatformOverviewController extends Controller
{
    protected PlatformBusinessMetricsService $platformMetrics;

    protected AffiliatePerformanceService $affiliatePerformance;

    protected AffiliateTargetTrackingService $targetTracking;

    public function __construct(
        PlatformBusinessMetricsService $platformMetrics,
        AffiliatePerformanceService $affiliatePerformance,
        AffiliateTargetTrackingService $targetTracking
    ) {
        $this->platformMetrics = $platformMetrics;
        $this->affiliatePerformance = $affiliatePerformance;
        $this->targetTracking = $targetTracking;
    }

    public function index(Request $request)
    {
        $filters = [
            'period' => $request->query('period', 'all'),
            'search' => $request->query('search', ''),
            'status' => $request->query('status', 'all'),
        ];

        $rows = $this->affiliatePerformance->affiliateRows($filters);
        $rows->appends($filters)->fragment('affiliate-performance');

        return view('platform.overview', [
            'platform' => $this->platformMetrics->overview(),
            'summary' => $this->affiliatePerformance->platformSummary($filters),
            'rows' => $rows,
            'filters' => $filters,
            'projectionSummary' => $this->targetTracking->projectionSummary(),
        ]);
    }
}
