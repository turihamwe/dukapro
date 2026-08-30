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

        if (! $business->isSubscriptionExpired()) {
            return $next($request);
        }

        $reason = $this->expiredReason($business);

        if ($user->can('manage-billing')) {
            if ($request->routeIs('tenant.dashboard')) {
                return $next($request);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $reason,
                    'redirect' => route('subscription.payment'),
                ], 402);
            }

            return redirect()
                ->route('tenant.dashboard', ['business' => $business->slug])
                ->with('warning', $reason);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $reason,
                'redirect' => route('subscription.payment'),
            ], 402);
        }

        return redirect()->route('subscription.payment')->with('warning', $reason);
    }

    protected function expiredReason($business): string
    {
        $trialExpired = $business->trial_ends_at && $business->trial_ends_at->isPast();
        $subscriptionInactive = in_array($business->subscription_status, ['inactive', 'expired'], true);
        $paidPeriodExpired = $business->subscription_status === 'active'
            && $business->subscription_ends_at
            && $business->subscription_ends_at->isPast();

        if ($trialExpired && $subscriptionInactive) {
            return 'Your trial has ended and no active subscription was found.';
        }

        if ($paidPeriodExpired) {
            return 'Your paid subscription period has ended.';
        }

        return 'Your trial or subscription has expired.';
    }
}
