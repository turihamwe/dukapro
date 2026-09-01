<?php

namespace App\Http\Controllers;

use App\Services\AffiliateReferralService;
use Illuminate\Http\Request;

class AffiliateReferralController extends Controller
{
    protected AffiliateReferralService $referralService;

    public function __construct(AffiliateReferralService $referralService)
    {
        $this->referralService = $referralService;
    }

    public function redirect(Request $request, string $code)
    {
        $affiliate = $this->referralService->captureCode($request, $code);

        if (! $affiliate) {
            return redirect()
                ->route('register')
                ->with('info', 'That referral link is invalid or no longer active.');
        }

        return redirect()->route('register');
    }
}
