<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\AffiliateCommission;
use App\Models\AffiliateWithdrawalRequest;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $affiliate = $user->affiliateProfile;

        abort_unless($affiliate, 404);

        $affiliate->load(['parent', 'teamMembers.user']);

        $primary = $affiliate->primaryAffiliate();

        $referredQuery = $affiliate->isSubAffiliate()
            ? $affiliate->attributedBusinesses()
            : $affiliate->referredBusinesses();

        $referredBusinesses = $referredQuery
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'phone', 'email', 'created_at', 'subscription_status', 'referring_affiliate_id']);

        $commissions = AffiliateCommission::query()
            ->where('affiliate_id', $primary->id)
            ->with('business:id,name')
            ->latest('id')
            ->limit(50)
            ->get();

        $withdrawals = $affiliate->withdrawalRequests()
            ->latest('id')
            ->limit(20)
            ->get();

        $stats = [
            'onboarded_count' => $referredBusinesses->count(),
            'total_commission' => (float) $primary->commissions()->sum('commission_amount'),
            'pending_commission' => (float) $primary->commissions()->where('status', 'pending')->sum('commission_amount'),
            'paid_commission' => (float) $primary->commissions()->where('status', 'paid')->sum('commission_amount'),
            'wallet_balance' => (float) $affiliate->wallet_balance,
        ];

        return view('affiliate.dashboard', [
            'affiliate' => $affiliate,
            'primaryAffiliate' => $primary,
            'referredBusinesses' => $referredBusinesses,
            'commissions' => $commissions,
            'withdrawals' => $withdrawals,
            'teamMembers' => $affiliate->isSubAffiliate() ? collect() : $affiliate->teamMembers,
            'stats' => $stats,
        ]);
    }
}
