<?php

namespace App\Http\Middleware;

use App\Support\PaymentWalletMode;
use Closure;
use Illuminate\Http\Request;

class EnsurePaymentWalletModeActive
{
    public function handle(Request $request, Closure $next)
    {
        $business = $request->user() ? $request->user()->business : null;

        if (! PaymentWalletMode::active($business)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Payment wallets are not enabled for this business.'], 404);
            }

            abort(404);
        }

        return $next($request);
    }
}
