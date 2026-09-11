<?php

namespace App\Http\Controllers;

use App\Services\AffiliateNetworkService;
use App\Services\AffiliateReferralService;
use App\Services\AffiliateRegistrationService;
use Illuminate\Http\Request;

class AffiliateApplicationController extends Controller
{
    protected AffiliateRegistrationService $registrationService;

    protected AffiliateReferralService $referralService;

    protected AffiliateNetworkService $networkService;

    public function __construct(
        AffiliateRegistrationService $registrationService,
        AffiliateReferralService $referralService,
        AffiliateNetworkService $networkService
    ) {
        $this->registrationService = $registrationService;
        $this->referralService = $referralService;
        $this->networkService = $networkService;
    }

    public function showApply()
    {
        $user = auth()->user();
        if ($user && $user->hasAffiliatePortalAccess()) {
            return redirect()->route('affiliate.dashboard');
        }

        if (! $this->registrationService->isRecruitmentOpen()) {
            return view('auth.affiliate-apply-closed');
        }

        return view('auth.affiliate-apply');
    }

    public function apply(Request $request)
    {
        if (! $this->registrationService->isRecruitmentOpen()) {
            return back()->withErrors(['email' => 'Affiliate recruitment is currently closed.']);
        }

        if ($request->filled('username')) {
            $request->merge(['username' => strtolower(trim($request->input('username')))]);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:50|alpha_dash|unique:users,username',
            'email' => 'required|email|max:255|unique:affiliates,email|unique:users,email',
            'phone' => 'required|string|max:30',
            'password' => 'required|string|min:8|confirmed',
            'application_message' => 'nullable|string|max:2000',
        ]);

        $affiliate = $this->registrationService->apply($data);

        return redirect()
            ->route('affiliate.login')
            ->with('success', 'Application submitted! Your referral code will be ' . $affiliate->code . ' once approved. Sign in to check your status.');
    }

    public function showTeamJoin(string $code)
    {
        $user = auth()->user();
        if ($user && $user->hasAffiliatePortalAccess()) {
            return redirect()->route('affiliate.dashboard');
        }

        $parent = $this->referralService->findTeamLeaderByCode($code);

        if (! $parent) {
            return redirect()
                ->route('affiliate.apply')
                ->with('info', 'That team invite link is invalid or no longer active.');
        }

        if (! $this->registrationService->isRecruitmentOpen()) {
            return view('auth.affiliate-apply-closed');
        }

        return view('auth.affiliate-team-join', [
            'parent' => $parent,
        ]);
    }

    public function storeTeamJoin(Request $request, string $code)
    {
        if (! $this->registrationService->isRecruitmentOpen()) {
            return back()->withErrors(['email' => 'Affiliate recruitment is currently closed.']);
        }

        $parent = $this->referralService->findTeamLeaderByCode($code);

        if (! $parent) {
            return redirect()
                ->route('affiliate.apply')
                ->with('info', 'That team invite link is invalid or no longer active.');
        }

        if ($request->filled('username')) {
            $request->merge(['username' => strtolower(trim($request->input('username')))]);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:50|alpha_dash|unique:users,username',
            'email' => 'required|email|max:255|unique:affiliates,email|unique:users,email',
            'phone' => 'required|string|max:30',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $affiliate = $this->networkService->createSubAffiliate($parent, $data);

        return redirect()
            ->route('affiliate.login')
            ->with('success', 'Welcome to ' . $parent->name . '\'s team! Your agent code is ' . $affiliate->code . '. Sign in to start referring businesses.');
    }
}
