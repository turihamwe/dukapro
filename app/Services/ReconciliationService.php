<?php

namespace App\Services;

use App\Models\EndOfDayReconciliation;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;

class ReconciliationService
{
    protected DamageService $damageService;

    protected ExpenseService $expenseService;

    protected WaiterShiftService $waiterShiftService;

    protected ReconciliationShortageService $shortageService;

    public function __construct(
        DamageService $damageService,
        ExpenseService $expenseService,
        WaiterShiftService $waiterShiftService,
        ReconciliationShortageService $shortageService
    ) {
        $this->damageService = $damageService;
        $this->expenseService = $expenseService;
        $this->waiterShiftService = $waiterShiftService;
        $this->shortageService = $shortageService;
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
        $expectedBankOther = $sales->where('payment_method', 'bank')->sum('total');

        $dailySummary = $this->calculateDailySummary($businessId, $date);

        return [
            'expected_cash' => round($expectedCash, 2),
            'expected_mobile_money' => round($expectedMobileMoney, 2),
            'expected_bank_other' => round($expectedBankOther, 2),
            'user_total_sales' => round((float) $sales->sum('total'), 2),
            'sale_count' => $sales->count(),
            'total_sales' => $dailySummary['total_sales'],
            'total_expenses' => $dailySummary['total_expenses'],
            'total_damages' => $dailySummary['total_damages'],
            'total_extra_cash' => $dailySummary['total_extra_cash'],
            'net_income' => $dailySummary['net_income'],
            'expenses' => $dailySummary['expenses'],
            'damages' => $dailySummary['damages'],
        ];
    }

    public function calculateDailySummary(int $businessId, Carbon $date): array
    {
        $totalSales = (float) Sale::query()
            ->where('business_id', $businessId)
            ->where('status', 'completed')
            ->whereDate('completed_at', $date)
            ->sum('total');

        $totalExpenses = (float) Expense::query()
            ->where('business_id', $businessId)
            ->whereDate('expense_date', $date)
            ->sum('amount');

        $damagesSummary = $this->damageService->summarizeForDate($businessId, $date);
        $totalDamages = (float) $damagesSummary['total_loss'];

        $expenses = Expense::query()
            ->with('user')
            ->where('business_id', $businessId)
            ->whereDate('expense_date', $date)
            ->orderBy('title')
            ->get();

        $netIncome = round($totalSales - $totalExpenses - $totalDamages, 2);

        $totalExtraCash = (float) EndOfDayReconciliation::query()
            ->where('business_id', $businessId)
            ->whereDate('reconciliation_date', $date)
            ->sum('extra_cash');

        return [
            'total_sales' => round($totalSales, 2),
            'total_expenses' => round($totalExpenses, 2),
            'total_damages' => round($totalDamages, 2),
            'total_extra_cash' => round($totalExtraCash, 2),
            'net_income' => $netIncome,
            'expenses' => $expenses,
            'damages' => $damagesSummary,
        ];
    }

    public function calculateMissingMoney(
        float $expectedCash,
        float $actualCash,
        float $actualMobile,
        float $actualBankOther,
        float $totalExpenses,
        float $totalDamages,
        float $extraCash = 0
    ): float {
        return round(
            $expectedCash - ($actualCash + $extraCash) - $actualMobile - $actualBankOther - $totalExpenses - $totalDamages,
            2
        );
    }

    public function submit(User $user, array $data): EndOfDayReconciliation
    {
        $date = Carbon::parse($data['reconciliation_date']);
        $expected = $this->calculateExpectedTotals($user->business_id, $user->id, $date);
        $dailySummary = $this->calculateDailySummary($user->business_id, $date);

        $actualCash = (float) $data['actual_cash'];
        $actualMobile = (float) ($data['actual_mobile_money'] ?? 0);
        $actualBankOther = (float) ($data['actual_bank_other'] ?? 0);
        $extraCash = (float) ($data['extra_cash'] ?? 0);

        $missingMoney = $this->calculateMissingMoney(
            $expected['expected_cash'],
            $actualCash,
            $actualMobile,
            $actualBankOther,
            $dailySummary['total_expenses'],
            $dailySummary['total_damages'],
            $extraCash
        );

        $reconciliation = EndOfDayReconciliation::updateOrCreate(
            [
                'business_id' => $user->business_id,
                'user_id' => $user->id,
                'reconciliation_date' => $date->toDateString(),
            ],
            [
                'expected_cash' => $expected['expected_cash'],
                'expected_mobile_money' => $expected['expected_mobile_money'],
                'expected_bank_other' => $expected['expected_bank_other'],
                'actual_cash' => $actualCash,
                'actual_mobile_money' => $actualMobile,
                'actual_bank_other' => $actualBankOther,
                'cash_variance' => round($actualCash - $expected['expected_cash'], 2),
                'mobile_variance' => round($actualMobile - $expected['expected_mobile_money'], 2),
                'missing_money' => $missingMoney,
                'total_sales' => $dailySummary['total_sales'],
                'total_expenses' => $dailySummary['total_expenses'],
                'total_damages' => $dailySummary['total_damages'],
                'extra_cash' => $extraCash,
                'net_income' => $dailySummary['net_income'],
                'notes' => $data['notes'] ?? null,
                'status' => 'submitted',
            ]
        );

        $this->shortageService->recordFromReconciliation($reconciliation, $user);

        return $reconciliation;
    }

    public function submitWithWaiterBalances(User $user, array $data): EndOfDayReconciliation
    {
        $reconciliation = $this->submit($user, $data);

        if ($user->business->usesShiftBalancing() && ! empty($data['bundle_waiter_balances'])) {
            $date = Carbon::parse($data['reconciliation_date']);
            $this->waiterShiftService->attachBalancesToReconciliation(
                $user->business_id,
                $date,
                $reconciliation->id
            );
        }

        return $reconciliation->fresh();
    }

    public function buildReportDetails(EndOfDayReconciliation $reconciliation): array
    {
        $date = Carbon::parse($reconciliation->reconciliation_date);
        $summary = $this->calculateDailySummary($reconciliation->business_id, $date);

        return array_merge($summary, [
            'reconciliation' => $reconciliation,
            'date' => $date,
            'waiter_balances' => $reconciliation->business->usesShiftBalancing()
                ? $this->waiterShiftService->balancesForDate($reconciliation->business_id, $date)
                : collect(),
        ]);
    }

    public function whatsAppShareUrl(EndOfDayReconciliation $reconciliation, ?string $recipientPhone = null): ?string
    {
        if (! $recipientPhone) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $recipientPhone);
        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 9 && $digits[0] === '0') {
            $digits = '256' . substr($digits, 1);
        } elseif (strlen($digits) === 10 && $digits[0] === '0') {
            $digits = '256' . substr($digits, 1);
        }

        $business = $reconciliation->business;
        $dateLabel = $reconciliation->reconciliation_date->format('M j, Y');

        $message = implode("\n", array_filter([
            "EOD Report — {$business->name}",
            "Date: {$dateLabel}",
            "Cashier: {$reconciliation->user->name}",
            '',
            'Balancing:',
            '• Expected cash: ' . format_money($reconciliation->expected_cash, $business),
            '• Actual cash: ' . format_money($reconciliation->actual_cash, $business),
            '• Mobile money: ' . format_money($reconciliation->actual_mobile_money, $business),
            '• Bank & other: ' . format_money($reconciliation->actual_bank_other ?? 0, $business),
            '• Expenses: ' . format_money($reconciliation->total_expenses ?? 0, $business),
            '• Damages: ' . format_money($reconciliation->total_damages ?? 0, $business),
            '• Extra cash: ' . format_money($reconciliation->extra_cash ?? 0, $business),
            '• Missing money: ' . format_money($reconciliation->missing_money ?? 0, $business),
        ]));

        return 'https://wa.me/' . $digits . '?text=' . rawurlencode($message);
    }
}
