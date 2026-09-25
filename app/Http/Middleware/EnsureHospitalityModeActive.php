<?php

namespace App\Http\Middleware;

use App\Support\BusinessModeCompliance;
use Closure;
use Illuminate\Http\Request;

class EnsureHospitalityModeActive
{
    public function handle(Request $request, Closure $next)
    {
        $business = $request->user()?->business;

        if (! BusinessModeCompliance::hospitalityModeActive($business)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Hospitality mode is not enabled for this business.'], 404);
            }

            abort(404);
        }

        return $next($request);
    }
}
