<?php

namespace App\Http\Middleware;

use App\Support\CashierMode;
use Closure;
use Illuminate\Http\Request;

class EnsureManagementAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->canSwitchToCashierMode() && CashierMode::isActive()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Exit Cashier Mode to access management features.'], 403);
            }

            return redirect()
                ->to(tenant_route('tenant.pos.index'))
                ->with('warning', 'You are in Cashier Mode. Switch back to Management to access this area.');
        }

        return $next($request);
    }
}
