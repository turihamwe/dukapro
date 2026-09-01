<?php

namespace App\Http\Controllers;

use App\Services\AffiliateRegistrationService;
use Illuminate\Http\Request;

class AffiliateApplicationController extends Controller
{
    protected AffiliateRegistrationService $registrationService;

    public function __construct(AffiliateRegistrationService $registrationService)
    {
        $this->registrationService = $registrationService;
    }

    public function showApply()
    {
        $user = auth()->user();
        if ($user && $user->isAffiliate()) {
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

        $data = $request->validate([
            'name' => 'required|string|max:255',
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
}
