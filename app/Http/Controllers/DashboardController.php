<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Support\AnalyticsDateRange;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->middleware('can:view-dashboard');
        $this->dashboardService = $dashboardService;
    }

    public function index(Request $request)
    {
        $range = AnalyticsDateRange::fromRequest($request);
        $business = $request->user()->business;
        $payload = $this->dashboardService->chartPayload($business, $range);

        return view('dashboard', [
            'business' => $business,
            'stats' => $payload['stats'],
            'range' => $range,
            'rangePresets' => AnalyticsDateRange::presets(),
            'revenueChart' => $payload['revenue_chart'],
            'stockChart' => $payload['stock_chart'],
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
            ],
            'revenue_chart' => array_merge($payload['revenue_chart'], [
                'total_formatted' => format_money($payload['revenue_chart']['total']),
            ]),
            'stock_chart' => $payload['stock_chart'],
        ]);
    }
}
