<?php

namespace App\Services;

use App\Helpers\SystemAuditLogger;
use App\Models\Shareholder;
use App\Models\ShareholderPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ShareholderPaymentService
{
    protected YoPaymentsService $yoPaymentsService;

    protected ShareholderRegistrationService $registrationService;

    public function __construct(
        YoPaymentsService $yoPaymentsService,
        ShareholderRegistrationService $registrationService
    ) {
        $this->yoPaymentsService = $yoPaymentsService;
        $this->registrationService = $registrationService;
    }

    public function initiateDeposit(Shareholder $shareholder, string $phoneNumber, string $provider = 'mtn'): array
    {
        if ($shareholder->status !== 'pending') {
            throw ValidationException::withMessages([
                'deposit' => 'Share deposit is only available while your application is pending approval.',
            ]);
        }

        if ($shareholder->hasCompletedDeposit()) {
            throw ValidationException::withMessages([
                'deposit' => 'Your share deposit has already been received.',
            ]);
        }

        $this->registrationService->assertPendingApplicationValid($shareholder);

        $amount = round((float) $shareholder->capital_invested, 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'deposit' => 'Invalid deposit amount for your selected shares.',
            ]);
        }

        $existingPending = $shareholder->pendingDepositPayment();
        if ($existingPending) {
            $existingPending->update(['status' => 'failed']);
        }

        $providerKey = $provider === 'airtel' ? 'airtel_money' : 'mtn_momo';
        $reference = 'SHR-' . strtoupper(Str::random(10));
        $narrative = platform_brand('name') . ' shareholder deposit';

        $payment = ShareholderPayment::create([
            'shareholder_id' => $shareholder->id,
            'amount' => $amount,
            'payment_method' => 'mobile_money',
            'reference' => $reference,
            'provider' => $providerKey,
            'status' => 'pending',
            'metadata' => [
                'phone_number' => $phoneNumber,
                'provider' => $provider,
                'shares' => (float) $shareholder->shares_owned,
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

            return [
                'success' => true,
                'reference' => $reference,
                'amount' => $payment->amount,
                'provider' => $provider,
                'simulated' => true,
                'message' => 'Sandbox mode: complete the simulated deposit to activate your shares.',
                'simulated_checkout_url' => route('shareholder.deposit.simulate', $reference),
            ];
        }

        $result = $this->yoPaymentsService->initiateCollection(
            $phoneNumber,
            (float) $payment->amount,
            $reference,
            $narrative,
            $provider
        );

        if (! $result['success']) {
            $payment->update([
                'status' => 'failed',
                'metadata' => array_merge($payment->metadata ?? [], [
                    'yo_response' => $result['yo_response'] ?? null,
                    'error' => $result['message'] ?? 'YoPayments request failed',
                ]),
            ]);

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

        return [
            'success' => true,
            'reference' => $reference,
            'amount' => $payment->amount,
            'provider' => $provider,
            'simulated' => false,
            'message' => $result['message'] ?? 'PIN prompt sent. Complete payment on your phone.',
        ];
    }

    public function handleWebhook(array $payload): array
    {
        $normalized = $this->yoPaymentsService->normalizeIpnPayload($payload);

        $reference = $payload['reference']
            ?? $normalized['external_ref']
            ?? $payload['external_ref']
            ?? $payload['ExternalReference']
            ?? $payload['CheckoutRequestID']
            ?? null;

        if (! $reference || ! Str::startsWith((string) $reference, 'SHR-')) {
            return ['success' => false, 'message' => 'Missing or invalid shareholder payment reference'];
        }

        $payment = ShareholderPayment::query()
            ->where('reference', $reference)
            ->first();

        if (! $payment) {
            return ['success' => false, 'message' => 'Shareholder payment not found'];
        }

        if ($payment->status === 'completed') {
            return ['success' => true, 'message' => 'Payment already processed'];
        }

        if (! empty($normalized['failed_transaction_reference']) || ! empty($normalized['verification'])) {
            $payment->update([
                'status' => 'failed',
                'metadata' => array_merge($payment->metadata ?? [], ['webhook' => $payload, 'yo_ipn' => $normalized]),
            ]);

            return ['success' => false, 'message' => 'Payment failed'];
        }

        $status = strtolower((string) ($payload['status'] ?? $payload['ResultCode'] ?? $payload['transaction_status'] ?? ''));

        $isYoSuccessIpn = ! empty($normalized['external_ref']) && ! empty($normalized['network_ref']);
        $isSuccess = $isYoSuccessIpn || in_array($status, ['completed', 'success', '0', 'paid', 'succeeded'], true);

        if (! $isSuccess) {
            $payment->update([
                'status' => 'failed',
                'metadata' => array_merge($payment->metadata ?? [], ['webhook' => $payload]),
            ]);

            return ['success' => false, 'message' => 'Payment failed'];
        }

        return DB::transaction(function () use ($payment, $payload, $reference) {
            $payment = ShareholderPayment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->status === 'completed') {
                return ['success' => true, 'message' => 'Payment already processed'];
            }

            $payment->update([
                'status' => 'completed',
                'paid_at' => Carbon::now(),
                'metadata' => array_merge($payment->metadata ?? [], ['webhook' => $payload]),
            ]);

            $shareholder = Shareholder::query()->whereKey($payment->shareholder_id)->lockForUpdate()->firstOrFail();

            try {
                $this->registrationService->approveFromPayment($shareholder);
            } catch (ValidationException $exception) {
                $payment->update([
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'approval_error' => $exception->errors(),
                    ]),
                ]);

                return [
                    'success' => false,
                    'message' => 'Payment received but shares could not be allocated. Contact support with reference ' . $reference . '.',
                ];
            }

            SystemAuditLogger::record(
                'shareholder_deposit_approved',
                'Shareholder #' . $shareholder->id . ' approved after YoPayments deposit ' . $reference,
                null,
                optional($shareholder->user)->id
            );

            return [
                'success' => true,
                'message' => 'Share deposit received. Your shares are now active.',
                'shareholder_id' => $shareholder->id,
            ];
        });
    }

    public function completeSimulatedPayment(string $reference, Shareholder $shareholder): array
    {
        $payment = ShareholderPayment::query()
            ->where('reference', $reference)
            ->where('shareholder_id', $shareholder->id)
            ->firstOrFail();

        return $this->handleWebhook([
            'reference' => $payment->reference,
            'status' => 'completed',
            'simulated' => true,
        ]);
    }
}
