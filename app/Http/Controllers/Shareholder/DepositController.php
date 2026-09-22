<?php

namespace App\Http\Controllers\Shareholder;

use App\Http\Controllers\Controller;
use App\Models\ShareholderPayment;
use App\Services\ShareholderPaymentService;
use App\Services\YoPaymentsService;
use Illuminate\Http\Request;

class DepositController extends Controller
{
    protected ShareholderPaymentService $paymentService;

    protected YoPaymentsService $yoPaymentsService;

    public function __construct(ShareholderPaymentService $paymentService, YoPaymentsService $yoPaymentsService)
    {
        $this->paymentService = $paymentService;
        $this->yoPaymentsService = $yoPaymentsService;
    }

    public function initiate(Request $request)
    {
        $shareholder = $request->user()->shareholderProfile;
        abort_unless($shareholder, 404);

        try {
            $data = $request->validate([
                'phone_number' => 'required|string|min:9|max:15',
                'provider' => 'required|in:mtn,airtel',
            ]);

            $result = $this->paymentService->initiateDeposit(
                $shareholder,
                $data['phone_number'],
                $data['provider']
            );

            if ($request->expectsJson()) {
                return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
            }

            return redirect()
                ->route('shareholder.dashboard')
                ->with('payment', $result);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'Deposit could not be started. Please try again.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }

            return back()->withErrors(['deposit' => $message]);
        }
    }

    public function simulate(Request $request, string $reference)
    {
        if (! $this->yoPaymentsService->shouldSimulate()) {
            abort(404);
        }

        $shareholder = $request->user()->shareholderProfile;
        abort_unless($shareholder, 404);

        $payment = ShareholderPayment::query()
            ->where('reference', $reference)
            ->where('shareholder_id', $shareholder->id)
            ->firstOrFail();

        return view('shareholder.deposit-simulate', compact('payment', 'shareholder'));
    }

    public function simulateComplete(Request $request, string $reference)
    {
        if (! $this->yoPaymentsService->shouldSimulate()) {
            abort(404);
        }

        $shareholder = $request->user()->shareholderProfile;
        abort_unless($shareholder, 404);

        $result = $this->paymentService->completeSimulatedPayment($reference, $shareholder);

        if ($result['success']) {
            return redirect()
                ->route('shareholder.dashboard')
                ->with('success', $result['message'] ?? 'Share deposit received. Your shares are now active.');
        }

        return back()->withErrors(['deposit' => $result['message'] ?? 'Could not complete simulated payment.']);
    }
}
