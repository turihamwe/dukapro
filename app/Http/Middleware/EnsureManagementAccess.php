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
            'tenant.inventory.catalog',
            'tenant.reconciliation.index',
            'tenant.reconciliation.create',
            'tenant.reconciliation.store',
            'tenant.reconciliation.show',
            'tenant.reconciliation.print',
            'tenant.expenses.create',
            'tenant.expenses.store',
            'tenant.expenses.categories.quick-store',
            'tenant.damages.index',
            'tenant.damages.store',
            'tenant.pos.index',
            'tenant.pos.search',
            'tenant.pos.checkout',
            'tenant.pos.send-kitchen',
            'tenant.waiter-shift.index',
            'tenant.waiter-shift.balance-all',
            'tenant.waiter-shift.show',
            'tenant.waiter-shift.settle-credit',
            'tenant.restaurant-orders.index',
            'tenant.restaurant-orders.print',
            'tenant.kitchen.index',
            'tenant.kitchen.poll',
            'tenant.kitchen.update-status',
            'tenant.kitchen.ready',
            'tenant.kitchen.settle',
            'tenant.kitchen.settle.store',
            'tenant.waiter-orders.index',
            'tenant.waiter-orders.search',
            'tenant.waiter-orders.place'
        );
    }
}
