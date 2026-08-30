<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\SubscriptionPayment;
use App\Scopes\TenantScope;
use App\Services\MobileMoneyService;
use App\Services\YoPaymentsService;
use App\Support\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    protected MobileMoneyService $mobileMoneyService;

    protected YoPaymentsService $yoPaymentsService;

    public function __construct(MobileMoneyService $mobileMoneyService, YoPaymentsService $yoPaymentsService)
    {
        $this->mobileMoneyService = $mobileMoneyService;
        $this->yoPaymentsService = $yoPaymentsService;
    }

    public function payment(Request $request)
    {
        $business = $request->user()->business;
        $plans = SubscriptionPlan::all();

        return view('subscription.payment', compact('business', 'plans'));
    }

    public function initiate(Request $request)
    {
        $this->authorize('manage-billing');

        $data = $request->validate([
            'phone_number' => 'required|string|min:9|max:15',
            'provider' => 'required|in:mtn,airtel',
            'plan' => ['required', Rule::in(SubscriptionPlan::keys())],
        ]);

        $result = $this->mobileMoneyService->initiatePayment(
            $request->user()->business,
            $data['phone_number'],
            $data['provider'],
            $data['plan']
        );

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return back()->with('payment', $result);
    }

    public function simulate(Request $request, string $reference)
    {
        if (! $this->yoPaymentsService->shouldSimulate()) {
            abort(404);
        }

        $payment = SubscriptionPayment::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('reference', $reference)
            ->where('business_id', $request->user()->business_id)
            ->firstOrFail();

        return view('subscription.simulate', compact('payment'));
    }

    public function simulateComplete(Request $request, string $reference)
    {
        if (! $this->yoPaymentsService->shouldSimulate()) {
            abort(404);
        }

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
            $days = (int) ($result['subscription_days'] ?? 30);

            return redirect()->route('tenant.dashboard', ['business' => $request->user()->business->slug])
                ->with('success', $result['message'] ?? ('Subscription activated for ' . $days . ' days.'));
        }

        return back()->withErrors(['payment' => $result['message']]);
    }
}
