<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();

                if ($user->isSuperAdmin()) {
                    return redirect()->route('superadmin.dashboard');
                }

                if ($user->business) {
                    if ($user->business->isSubscriptionExpired()) {
                        return redirect()->route('subscription.payment');
                    }

                    if ($user->isCashier()) {
                        return redirect()->route('tenant.pos.index', ['business' => $user->business->slug]);
                    }

                    return redirect()->route('tenant.dashboard', ['business' => $user->business->slug]);
                }

                return redirect('/');
            }
        }

        return $next($request);
    }
}
