<?php

namespace App\Http\Middleware;

use App\Support\BusinessModeCompliance;
use Closure;
use Illuminate\Http\Request;

class EnsureHospitalityModeActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $business = $user ? $user->business : null;

        if (! BusinessModeCompliance::hospitalityModeActive($business)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Hospitality mode is not enabled for this business.'], 404);
            }

            abort(404);
        }

        return $next($request);
    }
}
