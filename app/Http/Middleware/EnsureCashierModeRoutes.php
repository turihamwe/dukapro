<?php

namespace App\Http\Middleware;

use App\Models\Business;
use App\Support\CashierMode;
use Closure;
use Illuminate\Http\Request;

class EnsureCashierModeRoutes
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->isCashier()) {
            return $next($request);
        }

        if ($user->canSwitchToCashierMode() && ! CashierMode::isActive()) {
            if ($request->routeIs('tenant.pos.*', 'tenant.reconciliation.create', 'tenant.reconciliation.store', 'tenant.cashier-mode.*')) {
                return redirect()
                    ->to(tenant_route('tenant.dashboard'))
                    ->with('info', 'Switch into Cashier Mode to use the POS terminal.');
            }
        }

        return $next($request);
    }
}
