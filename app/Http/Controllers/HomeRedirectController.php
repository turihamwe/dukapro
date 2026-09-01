<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\LoginPortal;
use Illuminate\Http\RedirectResponse;

class HomeRedirectController extends Controller
{
    public function dashboard(): RedirectResponse
    {
        /** @var User|null $user */
        $user = auth()->user();

        abort_unless($user, 403);

        if ($user->isPlatformAdmin()) {
            return redirect()->route('superadmin.dashboard');
        }

        $portal = LoginPortal::get();

        if ($portal === LoginPortal::AFFILIATE && $user->hasAffiliatePortalAccess()) {
            return redirect()->route('affiliate.dashboard');
        }

        if ($portal === LoginPortal::SHAREHOLDER && $user->isDedicatedShareholderAccount()) {
            return redirect()->route('shareholder.dashboard');
        }

        if ($user->business) {
            if ($user->isCashier()) {
                return redirect()->route('tenant.pos.index', ['business' => $user->business->slug]);
            }

            if ($user->can('view-dashboard')) {
                return redirect()->route('tenant.dashboard', ['business' => $user->business->slug]);
            }

            return redirect()->route('tenant.pos.index', ['business' => $user->business->slug]);
        }

        if ($user->hasAffiliatePortalAccess()) {
            return redirect()->route('affiliate.dashboard');
        }

        if ($user->isDedicatedShareholderAccount()) {
            return redirect()->route('shareholder.dashboard');
        }

        abort(403);
    }
}
