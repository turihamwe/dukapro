<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next)
    {
        if (! SystemSetting::isMaintenanceMode()) {
            return $next($request);
        }

        $user = $request->user();
        if ($user && $user->isSuperAdmin()) {
            return $next($request);
        }

        if ($request->routeIs(
            'home',
            'login',
            'portal',
            'business.login',
            'superadmin.login',
            'logout',
            'logout.get',
            'register',
            'affiliate.apply',
            'affiliate.apply.store',
            'affiliate.login',
            'affiliate.login.store',
            'shareholder.apply',
            'shareholder.apply.store',
            'shareholder.login',
            'shareholder.login.store',
            'subscription.payment',
            'subscription.initiate',
            'subscription.simulate',
            'subscription.simulate.complete'
        )) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'System is under maintenance.'], 503);
        }

        return response()->view('maintenance', [], 503);
    }
}
