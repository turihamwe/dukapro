<?php

namespace App\Http\Middleware;

use App\Models\Business;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;

class EnsureTenantAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        /** @var Business|null $business */
        $business = $request->route('business');

        if (! $user || ! $user->business_id || $user->isPlatformAdmin()) {
            abort(403, 'You must belong to a business to access this area.');
        }

        if (! $business instanceof Business) {
            abort(404);
        }

        if ((int) $user->business_id !== (int) $business->id) {
            abort(403, 'You do not have access to this business.');
        }

        TenantContext::set($business->id);

        return $next($request);
    }
}
