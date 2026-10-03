<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Damage;
use App\Models\EndOfDayReconciliation;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SupplierCreditPayment;
use App\Models\SupplierCreditPurchase;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BusinessFinancialStatementService
{
    public function incomeStatement(int $businessId, Carbon $start, Carbon $end): array
    {
        $saleIds = Sale::query()
            ->where('business_id', $businessId)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$start, $end])
            ->pluck('id');

        $revenue = (float) Sale::query()
            ->where('business_id', $businessId)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$start, $end])
            ->sum('total');

        $costOfGoodsSold = 0.0;
        if ($saleIds->isNotEmpty()) {
            $costOfGoodsSold = round((float) SaleItem::query()
                ->whereIn('sale_id', $saleIds)
                ->get(['quantity', 'subtotal', 'cost_price'])
                ->sum(function (SaleItem $item) {
                    return (float) ($item->cost_price ?? 0) * (float) $item->quantity;
                }), 2);
        }

        $grossProfit = round($revenue - $costOfGoodsSold, 2);

        $expenseRows = Expense::query()
            ->where('business_id', $businessId)
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) {
                return [
                    'category' => (string) $row->category,
                    'total' => round((float) $row->total, 2),
                ];
            });

        $totalExpenses = round((float) $expenseRows->sum('total'), 2);

        $stockLosses = round((float) Damage::query()
            ->where('business_id', $businessId)
            ->whereBetween('damage_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->sum(function (Damage $damage) {
                return $damage->lossValue();
            }), 2);

        $netIncome = round($grossProfit - $totalExpenses - $stockLosses, 2);

        $creditPurchasesTotal = (float) SupplierCreditPurchase::query()
            ->where('business_id', $businessId)
            ->whereBetween('purchase_date', [$start->toDateString(), $end->toDateString()])
            ->sum('total_amount');

        $supplierPaymentsTotal = (float) SupplierCreditPayment::query()
            ->where('business_id', $businessId)
            ->whereBetween('paid_at', [$start, $end])
            ->sum('amount');

        return [
            'revenue' => round($revenue, 2),
            'cost_of_goods_sold' => $costOfGoodsSold,
            'gross_profit' => $grossProfit,
            'expenses_by_category' => $expenseRows,
            'total_expenses' => $totalExpenses,
            'stock_losses' => $stockLosses,
            'net_income' => $netIncome,
            'purchasing' => [
                'credit_purchases_received' => round($creditPurchasesTotal, 2),
                'supplier_payments_made' => round($supplierPaymentsTotal, 2),
            ],
        ];
    }

    public function balanceSheet(int $businessId, Carbon $asOf): array
    {
        $inventoryValue = round((float) Product::query()
            ->where('business_id', $businessId)
            ->where('is_active', true)
            ->get(['stock_quantity', 'cost_price'])
            ->sum(function (Product $product) {
                return $product->stock_quantity * (float) ($product->cost_price ?? 0);
            }), 2);

        $accountsReceivable = round((float) Customer::query()
            ->where('business_id', $businessId)
            ->sum('outstanding_balance'), 2);

        $openStatuses = [SupplierCreditPurchase::STATUS_OPEN, SupplierCreditPurchase::STATUS_PARTIAL];

        $accountsPayable = round((float) SupplierCreditPurchase::query()
            ->where('business_id', $businessId)
            ->whereIn('status', $openStatuses)
            ->selectRaw('COALESCE(SUM(total_amount - amount_paid), 0) as total')
            ->value('total'), 2);

        $cashSnapshot = $this->cashFromLatestEndOfDay($businessId, $asOf);

        $totalAssets = round($inventoryValue + $accountsReceivable + $cashSnapshot['total'], 2);
        $totalLiabilities = $accountsPayable;
        $netPosition = round($totalAssets - $totalLiabilities, 2);

        return [
            'as_of' => $asOf,
            'assets' => [
                'inventory' => $inventoryValue,
                'accounts_receivable' => $accountsReceivable,
                'cash_and_equivalents' => $cashSnapshot['total'],
                'cash_detail' => $cashSnapshot,
                'total' => $totalAssets,
            ],
            'liabilities' => [
                'accounts_payable' => $accountsPayable,
                'total' => $totalLiabilities,
            ],
            'net_position' => $netPosition,
        ];
    }

    protected function cashFromLatestEndOfDay(int $businessId, Carbon $asOf): array
    {
        $date = EndOfDayReconciliation::query()
            ->where('business_id', $businessId)
            ->whereDate('reconciliation_date', '<=', $asOf->toDateString())
            ->orderByDesc('reconciliation_date')
            ->value('reconciliation_date');

        if (! $date) {
            return [
                'date' => null,
                'cash' => 0.0,
                'mobile_money' => 0.0,
                'bank_other' => 0.0,
                'total' => 0.0,
                'report_count' => 0,
            ];
        }

        $rows = EndOfDayReconciliation::query()
            ->where('business_id', $businessId)
            ->whereDate('reconciliation_date', $date)
            ->get(['actual_cash', 'actual_mobile_money', 'actual_bank_other']);

        $cash = round((float) $rows->sum('actual_cash'), 2);
        $mobile = round((float) $rows->sum('actual_mobile_money'), 2);
        $bank = round((float) $rows->sum('actual_bank_other'), 2);

        return [
            'date' => Carbon::parse($date),
            'cash' => $cash,
            'mobile_money' => $mobile,
            'bank_other' => $bank,
            'total' => round($cash + $mobile + $bank, 2),
            'report_count' => $rows->count(),
        ];
    }
}
