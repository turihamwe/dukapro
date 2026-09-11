<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Services\AffiliateNetworkService;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    protected AffiliateNetworkService $networkService;

    public function __construct(AffiliateNetworkService $networkService)
    {
        $this->networkService = $networkService;
    }

    public function storeSubAffiliate(Request $request)
    {
        $parent = $request->user()->affiliateProfile;
        abort_unless($parent && ! $parent->isSubAffiliate(), 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email|unique:affiliates,email',
            'username' => 'required|string|max:50|alpha_dash|unique:users,username',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:30',
        ]);

        $this->networkService->createSubAffiliate($parent, $data, $request->user());

        return back()->with('success', 'Sub-affiliate added to your team.');
    }

    public function payout(Request $request)
    {
        $parent = $request->user()->affiliateProfile;
        abort_unless($parent && ! $parent->isSubAffiliate(), 403);

        $data = $request->validate([
            'sub_affiliate_id' => 'required|exists:affiliates,id',
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        $sub = Affiliate::query()->whereKey($data['sub_affiliate_id'])->firstOrFail();

        $this->networkService->payoutToSubAffiliate(
            $parent,
            $sub,
            (float) $data['amount'],
            $request->user(),
            $data['notes'] ?? null
        );

        return back()->with('success', 'Team payout sent successfully.');
    }
}
