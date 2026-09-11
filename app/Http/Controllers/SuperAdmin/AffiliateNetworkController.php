<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateWithdrawalRequest;
use App\Services\AffiliateNetworkService;
use Illuminate\Http\Request;

class AffiliateNetworkController extends Controller
{
    protected AffiliateNetworkService $networkService;

    public function __construct(AffiliateNetworkService $networkService)
    {
        $this->networkService = $networkService;
    }

    public function storeSubAffiliate(Request $request, Affiliate $affiliate)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        abort_if($affiliate->isSubAffiliate(), 422, 'Sub-affiliates cannot have their own teams.');

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email|unique:affiliates,email',
            'username' => 'required|string|max:50|alpha_dash|unique:users,username',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:30',
        ]);

        $this->networkService->createSubAffiliate($affiliate, $data, $request->user());

        return back()->with('success', 'Sub-affiliate added to team.');
    }

    public function processWithdrawal(Request $request, AffiliateWithdrawalRequest $withdrawal)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $data = $request->validate([
            'status' => 'required|in:paid,rejected',
        ]);

        $this->networkService->processWithdrawal($withdrawal, $request->user(), $data['status']);

        return back()->with('success', 'Withdrawal request updated.');
    }
}
