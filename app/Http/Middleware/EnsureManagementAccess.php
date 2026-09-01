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
            'tenant.inventory.create',
            'tenant.inventory.store',
            'tenant.inventory.catalog',
            'tenant.inventory.units.quick-store',
            'tenant.inventory.attributes.quick-store',
            'tenant.inventory.attributes.quick-value',
            'tenant.brands.index',
            'tenant.brands.create',
            'tenant.brands.store',
            'tenant.brands.quick-store',
            'tenant.reconciliation.index',
            'tenant.reconciliation.create',
            'tenant.reconciliation.store',
            'tenant.reconciliation.show',
            'tenant.reconciliation.print',
            'tenant.expenses.create',
            'tenant.expenses.store',
            'tenant.pos.index',
            'tenant.pos.search',
            'tenant.pos.checkout'
        );
    }
}
