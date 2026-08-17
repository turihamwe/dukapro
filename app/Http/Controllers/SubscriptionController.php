<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\SubscriptionPayment;
use App\Scopes\TenantScope;
use App\Services\MobileMoneyService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    protected MobileMoneyService $mobileMoneyService;

    public function __construct(MobileMoneyService $mobileMoneyService)
    {
        $this->mobileMoneyService = $mobileMoneyService;
    }

    public function payment(Request $request)
    {
        $business = $request->user()->business;

        return view('subscription.payment', compact('business'));
    }

    public function initiate(Request $request)
    {
        $this->authorize('manage-billing');

        $data = $request->validate([
            'phone_number' => 'required|string|min:9|max:15',
        ]);

        $result = $this->mobileMoneyService->initiatePayment(
            $request->user()->business,
            $data['phone_number']
        );

        return back()->with('payment', $result);
    }

    public function simulate(Request $request, string $reference)
    {
        $payment = SubscriptionPayment::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('reference', $reference)
            ->where('business_id', $request->user()->business_id)
            ->firstOrFail();

        return view('subscription.simulate', compact('payment'));
    }

    public function simulateComplete(Request $request, string $reference)
    {
        SubscriptionPayment::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('reference', $reference)
            ->where('business_id', $request->user()->business_id)
            ->firstOrFail();

        $result = $this->mobileMoneyService->handleWebhook([
            'reference' => $reference,
            'status' => 'completed',
            'simulated' => true,
        ]);

        if ($result['success']) {
            return redirect()->route('tenant.dashboard', ['business' => $request->user()->business->slug])
                ->with('success', 'Subscription activated for 30 days.');
        }

        return back()->withErrors(['payment' => $result['message']]);
    }
}
