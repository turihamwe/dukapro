<?php

namespace App\Services;

use App\Helpers\AuditLogger;
use App\Models\Business;
use App\Models\SubscriptionPayment;
use App\Scopes\TenantScope;
use App\Support\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Support\Str;

class MobileMoneyService
{
    protected YoPaymentsService $yoPaymentsService;

    public function __construct(YoPaymentsService $yoPaymentsService)
    {
        $this->yoPaymentsService = $yoPaymentsService;
    }

    public function initiatePayment(Business $business, string $phoneNumber, string $provider = 'mtn', string $planKey = 'monthly'): array
    {
        $plan = SubscriptionPlan::find($planKey);
        $providerKey = $provider === 'airtel' ? 'airtel_money' : 'mtn_momo';
        $reference = 'DUKA-' . strtoupper(Str::random(10));
        $narrative = 'DukaPro subscription - ' . $plan['label'];

        $payment = SubscriptionPayment::create([
            'business_id' => $business->id,
            'amount' => $plan['amount'],
            'payment_method' => 'mobile_money',
            'reference' => $reference,
            'provider' => $providerKey,
            'status' => 'pending',
            'metadata' => [
                'phone_number' => $phoneNumber,
                'provider' => $provider,
                'plan' => $plan['key'],
                'plan_label' => $plan['label'],
                'subscription_days' => $plan['days'],
                'initiated_at' => Carbon::now()->toIso8601String(),
                'environment' => $this->yoPaymentsService->config()['environment'],
            ],
        ]);

        if ($this->yoPaymentsService->shouldSimulate()) {
            $payment->update([
                'metadata' => array_merge($payment->metadata ?? [], [
                    'simulated' => true,
                    'pin_prompt_sent' => false,
                ]),
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
                'plan' => $plan['label'],
                'provider' => $provider,
                'simulated' => true,
                'message' => 'Sandbox mode: complete the simulated payment to activate your ' . strtolower($plan['label']) . ' plan.',
                'simulated_checkout_url' => url('/subscription/simulate/' . $reference),
            ];
        }

        $result = $this->yoPaymentsService->initiateCollection(
            $phoneNumber,
            (float) $payment->amount,
            $reference,
            $narrative
        );

        if (! $result['success']) {
            $payment->update([
                'status' => 'failed',
                'metadata' => array_merge($payment->metadata ?? [], [
                    'yo_response' => $result['yo_response'] ?? null,
                    'error' => $result['message'] ?? 'YoPayments request failed',
                ]),
            ]);

            AuditLogger::record(
                'subscription_payment_failed',
                $payment,
                null,
                $payment->fresh()->toArray(),
                $business->id
            );

            return [
                'success' => false,
                'reference' => $reference,
                'message' => $result['message'] ?? 'Payment request failed.',
            ];
        }

        $payment->update([
            'metadata' => array_merge($payment->metadata ?? [], [
                'simulated' => false,
                'pin_prompt_sent' => true,
                'yo_transaction_reference' => $result['transaction_reference'] ?? null,
                'yo_response' => $result['yo_response'] ?? null,
            ]),
        ]);

        AuditLogger::record(
            'subscription_payment_initiated',
            $payment,
            null,
            $payment->fresh()->toArray(),
            $business->id
        );

        return [
            'success' => true,
            'reference' => $reference,
            'amount' => $payment->amount,
            'plan' => $plan['label'],
            'provider' => $provider,
            'simulated' => false,
            'message' => $result['message'] ?? 'PIN prompt sent. Complete payment on your phone.',
        ];
    }

    public function handleWebhook(array $payload): array
    {
        $reference = $payload['reference']
            ?? $payload['external_ref']
            ?? $payload['ExternalReference']
            ?? $payload['CheckoutRequestID']
            ?? null;

        if (! $reference) {
            return ['success' => false, 'message' => 'Missing payment reference'];
        }

        $payment = SubscriptionPayment::withoutGlobalScope(TenantScope::class)
            ->where('reference', $reference)
            ->first();

        if (! $payment) {
            return ['success' => false, 'message' => 'Payment not found'];
        }

        if ($payment->status === 'completed') {
            return ['success' => true, 'message' => 'Payment already processed'];
        }

        $status = strtolower((string) ($payload['status'] ?? $payload['ResultCode'] ?? $payload['transaction_status'] ?? ''));

        if (! empty($payload['failed_transaction_reference'])) {
            $payment->update([
                'status' => 'failed',
                'metadata' => array_merge($payment->metadata ?? [], ['webhook' => $payload]),
            ]);

            return ['success' => false, 'message' => 'Payment failed'];
        }

        $isYoSuccessIpn = ! empty($payload['external_ref']) || ! empty($payload['network_ref']);
        $isSuccess = $isYoSuccessIpn || in_array($status, ['completed', 'success', '0', 'paid', 'succeeded'], true);

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
        $subscriptionDays = (int) ($payment->metadata['subscription_days'] ?? 30);
        $planLabel = $payment->metadata['plan_label'] ?? ($subscriptionDays >= 365 ? '1 Year' : '1 Month');

        $business->activateSubscription($subscriptionDays);

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
                'plan' => $payment->metadata['plan'] ?? null,
            ],
            $business->id
        );

        return [
            'success' => true,
            'message' => 'Subscription activated (' . $planLabel . ')',
            'business_id' => $business->id,
            'subscription_ends_at' => $business->fresh()->subscription_ends_at,
            'subscription_days' => $subscriptionDays,
        ];
    }
}
