<?php

namespace App\Services;

use App\Models\Damage;
use App\Models\EndOfDayReconciliation;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;

class ReconciliationService
{
    protected DamageService $damageService;

    public function __construct(DamageService $damageService)
    {
        $this->damageService = $damageService;
    }

    public function calculateExpectedTotals(int $businessId, int $userId, Carbon $date): array
    {
        $sales = Sale::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->whereDate('completed_at', $date)
            ->where('is_credit_sale', false)
            ->get();

        $expectedCash = $sales->where('payment_method', 'cash')->sum('total');
        $expectedMobileMoney = $sales->where('payment_method', 'mobile_money')->sum('total');

        return [
            'expected_cash' => round($expectedCash, 2),
            'expected_mobile_money' => round($expectedMobileMoney, 2),
            'sale_count' => $sales->count(),
            'damages' => $this->damageService->summarizeForDate($businessId, $date),
        ];
    }

    public function submit(User $user, array $data): EndOfDayReconciliation
    {
        $date = Carbon::parse($data['reconciliation_date']);
        $expected = $this->calculateExpectedTotals($user->business_id, $user->id, $date);

        $actualCash = (float) $data['actual_cash'];
        $actualMobile = (float) $data['actual_mobile_money'];

        return EndOfDayReconciliation::updateOrCreate(
            [
                'business_id' => $user->business_id,
                'user_id' => $user->id,
                'reconciliation_date' => $date->toDateString(),
            ],
            [
                'expected_cash' => $expected['expected_cash'],
                'expected_mobile_money' => $expected['expected_mobile_money'],
                'actual_cash' => $actualCash,
                'actual_mobile_money' => $actualMobile,
                'cash_variance' => round($actualCash - $expected['expected_cash'], 2),
                'mobile_variance' => round($actualMobile - $expected['expected_mobile_money'], 2),
                'notes' => $data['notes'] ?? null,
                'status' => 'submitted',
            ]
        );
    }
}
