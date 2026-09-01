<?php

namespace App\Http\Controllers;

use App\Services\ShareAllocationService;
use App\Services\ShareholderRegistrationService;
use Illuminate\Http\Request;

class ShareholderApplicationController extends Controller
{
    protected ShareholderRegistrationService $registrationService;

    protected ShareAllocationService $allocationService;

    public function __construct(
        ShareholderRegistrationService $registrationService,
        ShareAllocationService $allocationService
    ) {
        $this->registrationService = $registrationService;
        $this->allocationService = $allocationService;
    }

    public function showApply()
    {
        $user = auth()->user();
        if ($user && $user->hasShareholderPortalAccess()) {
            return redirect()->route('shareholder.dashboard');
        }

        if (! $this->registrationService->isSubscriptionOpen()) {
            return view('auth.shareholder-apply-closed');
        }

        return view('auth.shareholder-apply', [
            'remainingShares' => $this->allocationService->remainingShares(),
            'totalShares' => $this->allocationService->totalShares(),
            'pricePerShare' => $this->allocationService->pricePerShare(),
            'shareholderCount' => $this->allocationService->activeShareholderCount(),
            'maxShareholders' => $this->allocationService->maxShareholders(),
        ]);
    }

    public function apply(Request $request)
    {
        if (! $this->registrationService->isSubscriptionOpen()) {
            return back()->withErrors(['email' => 'Shareholder subscription is currently closed.']);
        }

        if ($request->filled('username')) {
            $request->merge(['username' => strtolower(trim($request->input('username')))]);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:50|alpha_dash|unique:users,username',
            'email' => 'required|email|max:255|unique:shareholders,email|unique:users,email',
            'phone' => 'required|string|max:30',
            'national_id' => 'nullable|string|max:50',
            'shares' => 'required|numeric|min:0.01',
            'password' => 'required|string|min:8|confirmed',
            'application_message' => 'nullable|string|max:2000',
        ]);

        $this->allocationService->validateAllocation((float) $data['shares']);

        $shareholder = $this->registrationService->apply($data);

        return redirect()
            ->route('shareholder.login')
            ->with('success', 'Application submitted for ' . number_format($shareholder->shares_owned, 2) . ' share(s). Sign in to track approval status.');
    }
}
