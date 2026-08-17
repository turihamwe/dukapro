<?php

namespace App\Http\Controllers;

use App\Services\MobileMoneyService;
use Illuminate\Http\Request;

class MobileMoneyWebhookController extends Controller
{
    protected MobileMoneyService $mobileMoneyService;

    public function __construct(MobileMoneyService $mobileMoneyService)
    {
        $this->mobileMoneyService = $mobileMoneyService;
    }

    public function handle(Request $request)
    {
        $secret = config('mobile_money.webhook_secret');

        if ($secret && $request->header('X-Webhook-Secret') !== $secret) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $result = $this->mobileMoneyService->handleWebhook($request->all());

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
