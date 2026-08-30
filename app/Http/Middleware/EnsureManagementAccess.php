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
            if ($this->isCashierOperationalRoute($request)) {
                return $next($request);
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Exit Cashier Mode to access management features.'], 403);
            }

            return redirect()
                ->to(tenant_route('tenant.pos.index'))
                ->with('warning', 'You are in Cashier Mode. Switch back to Management to access this area.');
        }

        return $next($request);
    }

    protected function isCashierOperationalRoute(Request $request): bool
    {
        return $request->routeIs(
            'tenant.inventory.index',
            'tenant.reconciliation.index',
            'tenant.reconciliation.show',
            'tenant.reconciliation.print'
        );
    }
}
