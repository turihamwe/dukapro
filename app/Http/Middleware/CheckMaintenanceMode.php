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
            'login',
            'logout',
            'logout.get',
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
