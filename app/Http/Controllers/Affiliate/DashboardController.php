<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\AffiliateCommission;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $affiliate = $user->affiliateProfile;

        abort_unless($affiliate, 404);

        $referredBusinesses = $affiliate->referredBusinesses()
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'phone', 'email', 'created_at', 'subscription_status']);

        $commissions = AffiliateCommission::query()
            ->where('affiliate_id', $affiliate->id)
            ->with('business:id,name')
            ->latest('id')
            ->limit(50)
            ->get();

        $stats = [
            'onboarded_count' => $referredBusinesses->count(),
            'total_commission' => (float) $affiliate->commissions()->sum('commission_amount'),
            'pending_commission' => (float) $affiliate->commissions()->where('status', 'pending')->sum('commission_amount'),
            'paid_commission' => (float) $affiliate->commissions()->where('status', 'paid')->sum('commission_amount'),
        ];

        return view('affiliate.dashboard', [
            'affiliate' => $affiliate,
            'referredBusinesses' => $referredBusinesses,
            'commissions' => $commissions,
            'stats' => $stats,
        ]);
    }
}
