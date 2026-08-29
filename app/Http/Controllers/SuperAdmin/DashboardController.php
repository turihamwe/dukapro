<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SystemAuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'businesses' => Business::count(),
            'users' => User::whereNotNull('business_id')->where('is_super_admin', false)->where('is_sub_admin', false)->count(),
            'products' => Product::count(),
            'customers' => Customer::count(),
            'sales' => Sale::where('status', 'completed')->count(),
            'expenses' => Expense::count(),
            'sales_volume' => (float) Sale::where('status', 'completed')->sum('total'),
            'expense_volume' => (float) Expense::sum('amount'),
            'active_subscriptions' => Business::where(function ($q) {
                $q->where('subscription_status', 'active')
                    ->where(function ($q2) {
                        $q2->whereNull('subscription_ends_at')
                            ->orWhere('subscription_ends_at', '>=', now());
                    });
            })->orWhere(function ($q) {
                $q->where('subscription_status', 'trial')
                    ->where('trial_ends_at', '>=', now());
            })->count(),
            'expired_or_inactive' => Business::where(function ($q) {
                $q->whereIn('subscription_status', ['expired', 'inactive'])
                    ->orWhere(function ($q2) {
                        $q2->where('subscription_status', 'trial')
                            ->where('trial_ends_at', '<', now());
                    })
                    ->orWhere(function ($q3) {
                        $q3->where('subscription_status', 'active')
                            ->where('subscription_ends_at', '<', now());
                    });
            })->count(),
            'recent_activity' => SystemAuditLog::with('business', 'user')->latest('created_at')->limit(10)->get(),
        ];

        $businesses = Business::withCount('users')
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'businesses_page');

        return view('superadmin.dashboard', compact('stats', 'businesses'));
    }
}
