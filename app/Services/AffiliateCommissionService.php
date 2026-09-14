<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\SubscriptionPayment;
use App\Support\AffiliateCommissionRates;
use Illuminate\Support\Facades\DB;
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

        $affiliate = $business->sponsor;

        if (! $affiliate) {
            return null;
        }

        try {
            return DB::transaction(function () use ($payment, $business, $affiliate) {
                $existing = AffiliateCommission::query()
                    ->where('subscription_payment_id', $payment->id)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    return $existing;
                }

                $affiliate = Affiliate::query()
                    ->whereKey($affiliate->id)
                    ->lockForUpdate()
                    ->first();

                if (! $affiliate) {
                    return null;
                }

                $rate = AffiliateCommissionRates::forReferredBusiness($affiliate, (int) $business->id);
                $amount = round((float) $payment->amount * $rate, 2);

                return AffiliateCommission::create([
                    'affiliate_id' => $affiliate->id,
                    'business_id' => $business->id,
                    'subscription_payment_id' => $payment->id,
                    'payment_amount' => (float) $payment->amount,
                    'commission_rate' => $rate,
                    'commission_amount' => $amount,
                    'status' => 'pending',
                ]);
            });
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
