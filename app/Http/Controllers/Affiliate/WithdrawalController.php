<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Services\AffiliateNetworkService;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    protected AffiliateNetworkService $networkService;

    public function __construct(AffiliateNetworkService $networkService)
    {
        $this->networkService = $networkService;
    }

    public function store(Request $request)
    {
        $affiliate = $request->user()->affiliateProfile;
        abort_unless($affiliate, 404);

        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payout_method' => 'required|string|max:50',
            'payout_account' => 'required|string|max:120',
            'notes' => 'nullable|string|max:500',
        ]);

        $this->networkService->requestWithdrawal($affiliate, $data);

        return back()->with('success', 'Withdrawal request submitted. We will process it shortly.');
    }
}
