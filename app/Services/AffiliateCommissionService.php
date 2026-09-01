<?php

namespace App\Services;

use App\Models\AffiliateCommission;
use App\Models\SubscriptionPayment;
use Illuminate\Support\Facades\Log;

class AffiliateCommissionService
{
    public function recordForPayment(SubscriptionPayment $payment): ?AffiliateCommission
    {
        if ($payment->status !== 'completed') {
            return null;
        }

        $business = $payment->business;

        if (! $business || ! $business->sponsor_id) {
            return null;
        }

        $existing = AffiliateCommission::query()
            ->where('subscription_payment_id', $payment->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $affiliate = $business->sponsor;

        if (! $affiliate) {
            return null;
        }

        $rate = (float) ($affiliate->commission_rate ?: config('affiliates.default_commission_rate', 0.10));
        $amount = round((float) $payment->amount * $rate, 2);

        try {
            return AffiliateCommission::create([
                'affiliate_id' => $affiliate->id,
                'business_id' => $business->id,
                'subscription_payment_id' => $payment->id,
                'payment_amount' => (float) $payment->amount,
                'commission_rate' => $rate,
                'commission_amount' => $amount,
                'status' => 'pending',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Affiliate commission recording failed', [
                'payment_id' => $payment->id,
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
