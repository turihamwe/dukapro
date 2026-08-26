<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\OnboardingService;
use App\Support\AnalyticsDateRange;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    protected OnboardingService $onboardingService;

    public function __construct(DashboardService $dashboardService, OnboardingService $onboardingService)
    {
        $this->middleware('can:view-dashboard');
        $this->dashboardService = $dashboardService;
        $this->onboardingService = $onboardingService;
    }

    public function index(Request $request)
    {
        $range = AnalyticsDateRange::fromRequest($request);
        $business = $request->user()->business;
        $onboarding = $this->onboardingService->status($business);
        $user = $request->user();

        $showFullDashboard = $onboarding['is_complete'] || ! $user->isOwner();

        $payload = $showFullDashboard
            ? $this->dashboardService->chartPayload($business, $range)
            : null;

        return view('dashboard', [
            'business' => $business,
            'onboarding' => $onboarding,
            'showFullDashboard' => $showFullDashboard,
            'stats' => $payload['stats'] ?? null,
            'range' => $range,
            'rangePresets' => AnalyticsDateRange::presets(),
            'revenueChart' => $payload['revenue_chart'] ?? null,
            'stockChart' => $payload['stock_chart'] ?? null,
        ]);
    }

    public function analytics(Request $request)
    {
        $range = AnalyticsDateRange::fromRequest($request);
        $business = $request->user()->business;
        $payload = $this->dashboardService->chartPayload($business, $range);

        return response()->json([
            'range' => $payload['range'],
            'stats' => [
                'period_sales' => $payload['stats']['period_sales'],
                'gross_profit' => $payload['stats']['gross_profit'],
                'gross_margin' => $payload['stats']['gross_margin'],
                'inventory_value' => $payload['stats']['inventory_value'],
                'sale_count' => $payload['stats']['sale_count'],
                'low_stock_count' => $payload['stats']['low_stock_count'],
                'period_sales_formatted' => format_money($payload['stats']['period_sales']),
                'gross_profit_formatted' => format_money($payload['stats']['gross_profit']),
                'inventory_value_formatted' => format_money($payload['stats']['inventory_value']),
                'executive' => $payload['stats']['executive'],
            ],
            'revenue_chart' => array_merge($payload['revenue_chart'], [
                'total_formatted' => format_money($payload['revenue_chart']['total']),
            ]),
            'stock_chart' => $payload['stock_chart'],
        ]);
    }
}
