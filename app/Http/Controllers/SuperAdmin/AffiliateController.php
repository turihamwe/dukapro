<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Services\AffiliatePerformanceService;
use App\Services\AffiliateTargetTrackingService;
use Illuminate\Http\Request;

class AffiliateController extends Controller
{
    protected AffiliatePerformanceService $performanceService;

    protected AffiliateTargetTrackingService $targetTrackingService;

    public function __construct(
        AffiliatePerformanceService $performanceService,
        AffiliateTargetTrackingService $targetTrackingService
    ) {
        $this->performanceService = $performanceService;
        $this->targetTrackingService = $targetTrackingService;
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
        $projectionSummary = $this->targetTrackingService->projectionSummary();

        return view('affiliates.index', compact('summary', 'rows', 'filters', 'projectionSummary'));
    }

    public function updateTarget(Request $request, Affiliate $affiliate)
    {
        abort_unless($request->user()->can('approve-affiliates'), 403);

        $data = $request->validate([
            'daily_shop_target' => 'required|numeric|min:0|max:9999',
        ]);

        $affiliate->update([
            'daily_shop_target' => $data['daily_shop_target'],
        ]);

        $tracking = $this->targetTrackingService->trackingPayload($affiliate->fresh());

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Daily target updated.',
                'tracking' => $tracking,
                'projection_summary' => $this->targetTrackingService->projectionSummary((float) $data['daily_shop_target']),
            ]);
        }

        return back()->with('success', 'Daily shop target updated for ' . $affiliate->name . '.');
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
