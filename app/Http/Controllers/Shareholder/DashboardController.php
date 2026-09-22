<?php

namespace App\Http\Controllers\Shareholder;

use App\Http\Controllers\Controller;
use App\Services\YoPaymentsService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected YoPaymentsService $yoPaymentsService;

    public function __construct(YoPaymentsService $yoPaymentsService)
    {
        $this->yoPaymentsService = $yoPaymentsService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $shareholder = $user->shareholderProfile;

        abort_unless($shareholder, 404);

        $shareholder->load(['earnings' => function ($query) {
            $query->latest('id')->limit(50);
        }]);

        $canPayDeposit = $shareholder->status === 'pending'
            && ! $shareholder->hasCompletedDeposit();

        return view('shareholder.dashboard', [
            'shareholder' => $shareholder,
            'earningsCap' => $shareholder->earningsCap(),
            'progressPercent' => $shareholder->earningsProgressPercent(),
            'remainingCapacity' => $shareholder->remainingEarningsCapacity(),
            'canPayDeposit' => $canPayDeposit,
            'depositReady' => $canPayDeposit && ($this->yoPaymentsService->isConfigured() || $this->yoPaymentsService->shouldSimulate()),
            'pendingDeposit' => $shareholder->pendingDepositPayment(),
        ]);
    }
}
