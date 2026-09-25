<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\BusinessEngagementService;
use App\Services\BusinessSalesMetricsService;
use App\Support\BusinessEngagementTier;
use App\Support\SuperAdmin\AdminListPagination;
use Illuminate\Http\Request;

class BusinessSalesController extends Controller
{
    protected BusinessSalesMetricsService $metrics;

    protected BusinessEngagementService $engagement;

    public function __construct(BusinessSalesMetricsService $metrics, BusinessEngagementService $engagement)
    {
        $this->metrics = $metrics;
        $this->engagement = $engagement;
    }

    public function index(Request $request)
    {
        $fromYear = $this->metrics->normalizeFromYear(
            $request->query('from_year') !== null ? (int) $request->query('from_year') : null
        );

        return view('business-sales.index', [
            'sales' => $this->metrics->overview($fromYear),
            'fromYear' => $fromYear,
        ]);
    }

    public function businesses(Request $request)
    {
        $stage = $request->query('stage', 'registered');
        $period = $request->query('period', 'all');
        $search = $request->query('search', '');

        if (! in_array($stage, ['registered', 'catalog', 'subscribed'], true)) {
            $stage = 'registered';
        }

        $businesses = $this->metrics
            ->businessesQuery($stage, $period, $search)
            ->paginate(AdminListPagination::PER_PAGE)
            ->withQueryString();

        return view('business-sales.businesses', [
            'businesses' => $businesses,
            'stage' => $stage,
            'period' => $period,
            'search' => $search,
            'stageLabel' => $this->metrics->stageLabel($stage),
            'periodLabel' => $this->metrics->periodLabel($period),
        ]);
    }

    public function engagement(Request $request)
    {
        $filter = $request->query('filter', 'all');
        $search = trim((string) $request->query('search', ''));

        $allowed = array_merge(['all', 'priority'], BusinessEngagementTier::all());
        if (! in_array($filter, $allowed, true)) {
            $filter = 'all';
        }

        $businesses = $this->engagement
            ->trialBusinessesQuery($filter, $search)
            ->paginate(AdminListPagination::PER_PAGE)
            ->withQueryString();

        return view('business-sales.engagement', [
            'businesses' => $businesses,
            'filter' => $filter,
            'search' => $search,
            'summary' => $this->engagement->summaryCounts(),
        ]);
    }
}
