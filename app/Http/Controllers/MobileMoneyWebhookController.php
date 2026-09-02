<?php

namespace App\Http\Controllers;

use App\Services\MobileMoneyService;
use App\Services\YoPaymentsService;
use Illuminate\Http\Request;

class MobileMoneyWebhookController extends Controller
{
    protected MobileMoneyService $mobileMoneyService;

    protected YoPaymentsService $yoPaymentsService;

    public function __construct(MobileMoneyService $mobileMoneyService, YoPaymentsService $yoPaymentsService)
    {
        $this->mobileMoneyService = $mobileMoneyService;
        $this->yoPaymentsService = $yoPaymentsService;
    }

    public function handle(Request $request)
    {
        $payload = $request->all();
        $isYoIpn = $request->filled('external_ref') || $request->filled('network_ref') || $request->filled('failed_transaction_reference');

        if (! $isYoIpn) {
            $secret = config('mobile_money.webhook_secret');

            if ($secret && $request->header('X-Webhook-Secret') !== $secret) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
        }

        $result = $this->mobileMoneyService->handleWebhook($payload);

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
