<?php

namespace App\Services;

use App\Helpers\AuditLogger;
use App\Models\Business;
use App\Models\SubscriptionPayment;
use Carbon\Carbon;
use Illuminate\Support\Str;

class MobileMoneyService
{
    public function initiatePayment(Business $business, string $phoneNumber): array
    {
        $reference = 'DUKA-' . strtoupper(Str::random(10));

        $payment = SubscriptionPayment::create([
            'business_id' => $business->id,
            'amount' => $business->subscription_amount,
            'payment_method' => 'mobile_money',
            'reference' => $reference,
            'provider' => 'local_mpesa',
            'status' => 'pending',
            'metadata' => [
                'phone_number' => $phoneNumber,
                'initiated_at' => Carbon::now()->toIso8601String(),
            ],
        ]);

        AuditLogger::record(
            'subscription_payment_initiated',
            $payment,
            null,
            $payment->toArray(),
            $business->id
        );

        return [
            'success' => true,
            'reference' => $reference,
            'amount' => $payment->amount,
            'message' => 'STK push initiated. Complete payment on your phone.',
            'simulated_checkout_url' => url('/subscription/simulate/' . $reference),
        ];
    }

    public function handleWebhook(array $payload): array
    {
        $reference = $payload['reference'] ?? $payload['CheckoutRequestID'] ?? null;
        $status = strtolower($payload['status'] ?? $payload['ResultCode'] ?? '');

        if (! $reference) {
            return ['success' => false, 'message' => 'Missing payment reference'];
        }

        $payment = SubscriptionPayment::withoutGlobalScope(TenantScope::class)
            ->where('reference', $reference)->first();

        if (! $payment) {
            return ['success' => false, 'message' => 'Payment not found'];
        }

        if ($payment->status === 'completed') {
            return ['success' => true, 'message' => 'Payment already processed'];
        }

        $isSuccess = in_array($status, ['completed', 'success', '0', 'paid'], true);

        if (! $isSuccess) {
            $payment->update([
                'status' => 'failed',
                'metadata' => array_merge($payment->metadata ?? [], ['webhook' => $payload]),
            ]);

            return ['success' => false, 'message' => 'Payment failed'];
        }

        $payment->update([
            'status' => 'completed',
            'paid_at' => Carbon::now(),
            'metadata' => array_merge($payment->metadata ?? [], ['webhook' => $payload]),
        ]);

        $business = $payment->business;
        $oldStatus = $business->subscription_status;
        $oldEndsAt = optional($business->subscription_ends_at)->toDateTimeString();

        $business->activateSubscription(30);

        AuditLogger::record(
            'subscription_activated',
            $business->fresh(),
            [
                'subscription_status' => $oldStatus,
                'subscription_ends_at' => $oldEndsAt,
            ],
            [
                'subscription_status' => $business->fresh()->subscription_status,
                'subscription_ends_at' => $business->fresh()->subscription_ends_at,
                'payment_reference' => $reference,
            ],
            $business->id
        );

        return [
            'success' => true,
            'message' => 'Subscription activated for 30 days',
            'business_id' => $business->id,
            'subscription_ends_at' => $business->fresh()->subscription_ends_at,
        ];
    }
}
