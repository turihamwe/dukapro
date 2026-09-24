<?php

namespace App\Support;

use App\Models\User;

class AppLogoUrl
{
    public static function resolve(): string
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user) {
            return route('home');
        }

        if ($user->isPlatformAdmin()) {
            return route('superadmin.dashboard');
        }

        $portal = LoginPortal::get();

        if ($portal === LoginPortal::AFFILIATE && $user->hasAffiliatePortalAccess()) {
            return route('affiliate.dashboard');
        }

        if ($portal === LoginPortal::SHAREHOLDER && $user->isDedicatedShareholderAccount()) {
            return route('shareholder.dashboard');
        }

        if ($user->business) {
            if ($user->isWaiter() && $user->can('access-waiter-orders')) {
                return route('tenant.waiter-orders.index', ['business' => $user->business->slug]);
            }

            if ($user->isChef() && $user->can('access-kitchen')) {
                return route('tenant.kitchen.index', ['business' => $user->business->slug]);
            }

            if ($user->isCashier()) {
                return route('tenant.pos.index', ['business' => $user->business->slug]);
            }

            if ($user->can('view-dashboard')) {
                return route('tenant.dashboard', ['business' => $user->business->slug]);
            }

            if ($user->can('access-waiter-orders')) {
                return route('tenant.waiter-orders.index', ['business' => $user->business->slug]);
            }

            if ($user->can('access-kitchen')) {
                return route('tenant.kitchen.index', ['business' => $user->business->slug]);
            }

            return route('tenant.pos.index', ['business' => $user->business->slug]);
        }

        if ($user->hasAffiliatePortalAccess()) {
            return route('affiliate.dashboard');
        }

        if ($user->isDedicatedShareholderAccount()) {
            return route('shareholder.dashboard');
        }

        return route('home');
    }
}
