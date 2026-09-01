<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAffiliate
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! $user->hasAffiliatePortalAccess()) {
            abort(403, 'Affiliate access only.');
        }

        if ($user->isPlatformAdmin()) {
            abort(403);
        }

        return $next($request);
    }
}
