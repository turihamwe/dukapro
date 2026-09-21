<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\AffiliateCommission;
use App\Models\AffiliateWithdrawalRequest;
use App\Services\AffiliateAttributionService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected AffiliateAttributionService $attributionService;

    public function __construct(AffiliateAttributionService $attributionService)
    {
        $this->attributionService = $attributionService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $affiliate = $user->affiliateProfile;

        abort_unless($affiliate, 404);

        $affiliate->load([
            'parent',
            'teamMembers.user',
            'teamMembers' => function ($query) {
                $query->withCount('attributedBusinesses');
            },
        ]);

        $primary = $affiliate->primaryAffiliate();

        $referralFilter = $request->query('referrals', 'all');
        if (! in_array($referralFilter, ['all', 'direct', 'sub'], true)) {
            $referralFilter = 'all';
        }

        $referredQuery = $affiliate->isSubAffiliate()
            ? $affiliate->attributedBusinesses()
            : $affiliate->referredBusinesses();

        if (! $affiliate->isSubAffiliate()) {
            $referredQuery = $this->attributionService->applyReferralTypeFilter($referredQuery, $referralFilter);
        }

        $referredBusinesses = $referredQuery
            ->with('referringAffiliate:id,name,code')
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'phone', 'email', 'created_at', 'subscription_status', 'referring_affiliate_id']);

        $referralAttribution = $affiliate->isSubAffiliate()
            ? null
            : $this->attributionService->referralCounts($affiliate);

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
            'referralFilter' => $referralFilter,
            'referralAttribution' => $referralAttribution,
            'commissions' => $commissions,
            'withdrawals' => $withdrawals,
            'teamMembers' => $affiliate->isSubAffiliate() ? collect() : $affiliate->teamMembers,
            'stats' => $stats,
        ]);
    }
}
