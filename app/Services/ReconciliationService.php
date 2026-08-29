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

    public function __construct(DamageService $damageService, ExpenseService $expenseService)
    {
        $this->damageService = $damageService;
        $this->expenseService = $expenseService;
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

        $dailySummary = $this->calculateDailySummary($businessId, $date);

        return [
            'expected_cash' => round($expectedCash, 2),
            'expected_mobile_money' => round($expectedMobileMoney, 2),
            'sale_count' => $sales->count(),
            'total_sales' => $dailySummary['total_sales'],
            'total_expenses' => $dailySummary['total_expenses'],
            'total_damages' => $dailySummary['total_damages'],
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

        return [
            'total_sales' => round($totalSales, 2),
            'total_expenses' => round($totalExpenses, 2),
            'total_damages' => round($totalDamages, 2),
            'net_income' => $netIncome,
            'expenses' => $expenses,
            'damages' => $damagesSummary,
        ];
    }

    public function submit(User $user, array $data): EndOfDayReconciliation
    {
        $date = Carbon::parse($data['reconciliation_date']);
        $expected = $this->calculateExpectedTotals($user->business_id, $user->id, $date);
        $dailySummary = $this->calculateDailySummary($user->business_id, $date);

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
                'total_sales' => $dailySummary['total_sales'],
                'total_expenses' => $dailySummary['total_expenses'],
                'total_damages' => $dailySummary['total_damages'],
                'net_income' => $dailySummary['net_income'],
                'notes' => $data['notes'] ?? null,
                'status' => 'submitted',
            ]
        );
    }

    public function buildReportDetails(EndOfDayReconciliation $reconciliation): array
    {
        $date = Carbon::parse($reconciliation->reconciliation_date);
        $summary = $this->calculateDailySummary($reconciliation->business_id, $date);

        return array_merge($summary, [
            'reconciliation' => $reconciliation,
            'date' => $date,
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
        $damages = $reconciliation->total_damages ?? 0;

        $message = implode("\n", array_filter([
            "EOD Report — {$business->name}",
            "Date: {$dateLabel}",
            "Cashier: {$reconciliation->user->name}",
            '',
            'Daily balancing:',
            '• Total sales: ' . format_money($reconciliation->total_sales ?? 0, $business),
            '• Expenses: ' . format_money($reconciliation->total_expenses ?? 0, $business),
            '• Damages: ' . format_money($damages, $business),
            '• Net income: ' . format_money($reconciliation->net_income ?? 0, $business),
            '',
            'Cash drawer:',
            '• Expected cash: ' . format_money($reconciliation->expected_cash, $business),
            '• Actual cash: ' . format_money($reconciliation->actual_cash, $business),
            '• Cash variance: ' . format_money($reconciliation->cash_variance, $business),
            '• Mobile variance: ' . format_money($reconciliation->mobile_variance, $business),
        ]));

        return 'https://wa.me/' . $digits . '?text=' . rawurlencode($message);
    }
}
