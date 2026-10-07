<?php

namespace App\Http\Middleware;

use App\Support\CustomerCreditMode;
use Closure;
use Illuminate\Http\Request;

class EnsureCustomerCreditModeActive
{
    public function handle(Request $request, Closure $next)
    {
        $business = $request->user() ? $request->user()->business : null;

        if (! CustomerCreditMode::active($business)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Customer credit is not enabled for this business.'], 404);
            }

            abort(404);
        }

        return $next($request);
    }
}
