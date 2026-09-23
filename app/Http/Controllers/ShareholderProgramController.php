<?php

namespace App\Http\Controllers;

use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Services\ShareAllocationService;
use App\Services\ShareholderRegistrationService;
use Illuminate\Http\Request;

class ShareholderProgramController extends Controller
{
    public const POOL_SUBSCRIPTION_PERCENT = 10;

    /** UGX paid to the pool per active subscribing business, per whole share (100-share pool). */
    public const UGX_PER_BUSINESS_PER_SHARE = 100;

    /** Marketed program return cap (10× capital per share). */
    public const PROGRAM_CAP_MULTIPLIER = 10;

    protected ShareAllocationService $allocationService;

    protected ShareholderRegistrationService $registrationService;

    public function __construct(
        ShareAllocationService $allocationService,
        ShareholderRegistrationService $registrationService
    ) {
        $this->allocationService = $allocationService;
        $this->registrationService = $registrationService;
    }

    public function index()
    {
        $totalShares = $this->allocationService->totalShares();
        $remainingShares = $this->allocationService->remainingShares();
        $allocatedShares = max(0, $totalShares - $remainingShares);

        return view('shareholders.index', [
            'brand' => platform_brand('name'),
            'subscriptionOpen' => $this->registrationService->isSubscriptionOpen(),
            'totalShares' => $totalShares,
            'remainingShares' => $remainingShares,
            'allocatedShares' => $allocatedShares,
            'pricePerShare' => $this->allocationService->pricePerShare(),
            'shareholderCount' => $this->allocationService->activeShareholderCount(),
            'maxShareholders' => $this->allocationService->maxShareholders(),
            'activeSubscribers' => Business::query()
                ->where('is_active', true)
                ->where('subscription_status', SubscriptionStatus::ACTIVE)
                ->count(),
            'liveBusinesses' => Business::query()->where('is_active', true)->count(),
            'poolPercent' => self::POOL_SUBSCRIPTION_PERCENT,
            'ugxPerBusinessPerShare' => self::UGX_PER_BUSINESS_PER_SHARE,
            'capMultiplier' => self::PROGRAM_CAP_MULTIPLIER,
            'exampleSubscription' => 100_000,
            'calculatorMaxShares' => (int) min(100, max(1, floor($remainingShares))),
        ]);
    }

    public function start(Request $request)
    {
        if (! $this->registrationService->isSubscriptionOpen()) {
            return back()->with('error', 'Shareholder subscriptions are temporarily closed. Contact support for updates.');
        }

        $remaining = $this->allocationService->remainingShares();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:30',
            'shares' => 'required|numeric|min:1|max:' . max(1, min(100, $remaining)),
        ]);

        try {
            $this->allocationService->validateAllocation((float) $data['shares'], null, true);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return back()
                ->withInput()
                ->withErrors($exception->errors());
        }

        $request->session()->put('shareholder_lead', [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'shares' => $data['shares'],
        ]);

        return redirect()
            ->route('shareholder.apply')
            ->with('success', 'Complete your secure application to reserve your share allocation.');
    }
}
