<?php

namespace App\Http\Middleware;

use App\Support\SupplierCreditMode;
use Closure;
use Illuminate\Http\Request;

class EnsureSupplierCreditModeActive
{
    public function handle(Request $request, Closure $next)
    {
        $business = $request->user() ? $request->user()->business : null;

        if (! SupplierCreditMode::active($business)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Supplier credit is not enabled for this business.'], 404);
            }

            abort(404);
        }

        return $next($request);
    }
}
