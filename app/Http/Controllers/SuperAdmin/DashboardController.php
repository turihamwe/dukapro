<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\SystemAuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'businesses' => Business::count(),
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
            'users' => User::where('is_super_admin', false)->count(),
            'recent_activity' => SystemAuditLog::with('business', 'user')->latest('created_at')->limit(10)->get(),
        ];

        $businesses = Business::withCount('users')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('superadmin.dashboard', compact('stats', 'businesses'));
    }
}
