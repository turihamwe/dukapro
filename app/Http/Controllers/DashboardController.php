<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view-dashboard');

        $business = $request->user()->business;

        $stats = [
            'products' => $business->products()->count(),
            'today_sales' => $business->sales()->whereDate('completed_at', today())->sum('total'),
        ];

        if (Gate::allows('view-analytics')) {
            $stats['outstanding_debt'] = $business->customers()->sum('outstanding_balance');
        }

        return view('dashboard', compact('business', 'stats'));
    }
}
