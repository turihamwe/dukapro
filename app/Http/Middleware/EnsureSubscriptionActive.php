<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSubscriptionActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! $user->business) {
            return $next($request);
        }

        $business = $user->business;

        $trialExpired = $business->trial_ends_at && $business->trial_ends_at->isPast();
        $subscriptionInactive = in_array($business->subscription_status, ['inactive', 'expired'], true);
        $paidPeriodExpired = $business->subscription_status === 'active'
            && $business->subscription_ends_at
            && $business->subscription_ends_at->isPast();

        if ($business->isSubscriptionExpired()) {
            $reason = $trialExpired && $subscriptionInactive
                ? 'Your trial has ended and no active subscription was found.'
                : ($paidPeriodExpired
                    ? 'Your paid subscription period has ended.'
                    : 'Your trial or subscription has expired.');

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $reason,
                    'redirect' => route('subscription.payment'),
                ], 402);
            }

            return redirect()->route('subscription.payment')->with('warning', $reason);
        }

        return $next($request);
    }
}
