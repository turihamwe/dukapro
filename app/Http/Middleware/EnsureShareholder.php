<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureShareholder
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! $user->hasShareholderPortalAccess()) {
            abort(403, 'Shareholder access only.');
        }

        if ($user->isPlatformAdmin()) {
            abort(403);
        }

        return $next($request);
    }
}
