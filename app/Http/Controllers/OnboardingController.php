<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Services\OnboardingService;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    protected OnboardingService $onboardingService;

    public function __construct(OnboardingService $onboardingService)
    {
        $this->onboardingService = $onboardingService;
        $this->middleware('can:manage-settings');
    }

    public function soleProprietor(Request $request, Business $business)
    {
        abort_unless($request->user()->isOwner(), 403);

        $this->onboardingService->markSoleProprietor($business);

        return redirect()
            ->to(tenant_route('tenant.dashboard'))
            ->with('success', 'Marked as sole proprietor. You can add staff later from the team page.');
    }
}
