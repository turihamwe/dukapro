<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Business;
use App\Models\Sale;
use App\Models\ShiftWaiterBalance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WaiterShiftService
{
    protected DebtLedgerService $debtLedgerService;

    public function __construct(DebtLedgerService $debtLedgerService)
    {
        $this->debtLedgerService = $debtLedgerService;
    }

    public function floorStaff(Business $business): Collection
    {
        return User::query()
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->whereIn('role', UserRole::staffRoles())
            ->orderBy('name')
            ->get(['id', 'name', 'role', 'username']);
    }

    public function waitersWithOrders(int $businessId, Carbon $date): Collection
    {
        return Sale::query()
            ->where('business_id', $businessId)
            ->where('status', 'completed')
            ->whereDate('completed_at', $date)
            ->whereNotNull('waiter_id')
            ->distinct()
            ->pluck('waiter_id');
    }

    public function calculateWaiterSummary(int $businessId, int $waiterId, Carbon $date): array
    {
        $sales = Sale::query()
            ->where('business_id', $businessId)
            ->where('waiter_id', $waiterId)
            ->where('status', 'completed')
            ->whereDate('completed_at', $date)
            ->get();

        $paidSales = $sales->where('is_credit_sale', false);
        $unsettledCredit = $sales->where('is_credit_sale', true)->whereNull('credit_settled_at');
        $settledCredit = $sales->where('is_credit_sale', true)->whereNotNull('credit_settled_at');

        $mobileSales = $paidSales->where('payment_method', 'mobile_money');

        return [
            'order_count' => $sales->count(),
            'expected_cash' => round((float) $paidSales->where('payment_method', 'cash')->sum('total'), 2),
            'expected_mobile_airtel' => round((float) $mobileSales->where('mobile_money_provider', 'airtel')->sum('total'), 2),
            'expected_mobile_mtn' => round((float) $mobileSales->where('mobile_money_provider', 'mtn')->sum('total'), 2),
            'expected_mobile_unspecified' => round((float) $mobileSales->whereNull('mobile_money_provider')->sum('total'), 2),
            'expected_bank_other' => round((float) $paidSales->where('payment_method', 'bank')->sum('total'), 2),
            'expected_credit' => round((float) $unsettledCredit->sum('total'), 2),
            'credit_collected' => round((float) $settledCredit->sum('total'), 2),
            'sales' => $sales,
        ];
    }

    public function summarizeShift(Business $business, Carbon $date): array
    {
        $staff = $this->floorStaff($business);
        $waiterIds = $this->waitersWithOrders($business->id, $date);

        $rows = $staff->map(function (User $waiter) use ($business, $date, $waiterIds) {
            $summary = $this->calculateWaiterSummary($business->id, $waiter->id, $date);
            $balance = ShiftWaiterBalance::query()
                ->where('business_id', $business->id)
                ->where('shift_date', $date->toDateString())
                ->where('waiter_user_id', $waiter->id)
                ->first();

            if ($summary['expected_mobile_unspecified'] > 0) {
                $summary['expected_mobile_mtn'] += $summary['expected_mobile_unspecified'];
            }

            return [
                'waiter' => $waiter,
                'summary' => $summary,
                'balance' => $balance,
                'has_activity' => $waiterIds->contains($waiter->id),
            ];
        })->filter(function (array $row) {
            return $row['has_activity'] || $row['balance'];
        })->values();

        return [
            'date' => $date,
            'rows' => $rows,
            'totals' => $this->aggregateExpected($rows),
        ];
    }

    public function balanceAllWaiters(User $cashier, Carbon $date, array $submissions): Collection
    {
        $business = $cashier->business;

        return DB::transaction(function () use ($cashier, $business, $date, $submissions) {
            $saved = collect();

            foreach ($submissions as $submission) {
                $waiterId = (int) ($submission['waiter_user_id'] ?? 0);
                if ($waiterId <= 0) {
                    continue;
                }

                $expected = $this->calculateWaiterSummary($business->id, $waiterId, $date);
                if ($expected['expected_mobile_unspecified'] > 0) {
                    $expected['expected_mobile_mtn'] += $expected['expected_mobile_unspecified'];
                }

                $actualCash = (float) ($submission['actual_cash'] ?? 0);
                $actualAirtel = (float) ($submission['actual_mobile_airtel'] ?? 0);
                $actualMtn = (float) ($submission['actual_mobile_mtn'] ?? 0);
                $actualBank = (float) ($submission['actual_bank_other'] ?? 0);
                $actualCredit = (float) ($submission['actual_credit_collected'] ?? 0);

                $expectedTotal = $expected['expected_cash']
                    + $expected['expected_mobile_airtel']
                    + $expected['expected_mobile_mtn']
                    + $expected['expected_bank_other']
                    + $expected['expected_credit'];

                $actualTotal = $actualCash + $actualAirtel + $actualMtn + $actualBank + $actualCredit;
                $shortage = round($expectedTotal - $actualTotal, 2);

                $record = ShiftWaiterBalance::updateOrCreate(
                    [
                        'business_id' => $business->id,
                        'shift_date' => $date->toDateString(),
                        'waiter_user_id' => $waiterId,
                    ],
                    [
                        'cashier_user_id' => $cashier->id,
                        'expected_cash' => $expected['expected_cash'],
                        'expected_mobile_airtel' => $expected['expected_mobile_airtel'],
                        'expected_mobile_mtn' => $expected['expected_mobile_mtn'],
                        'expected_bank_other' => $expected['expected_bank_other'],
                        'expected_credit' => $expected['expected_credit'],
                        'actual_cash' => $actualCash,
                        'actual_mobile_airtel' => $actualAirtel,
                        'actual_mobile_mtn' => $actualMtn,
                        'actual_bank_other' => $actualBank,
                        'actual_credit_collected' => $actualCredit,
                        'shortage' => $shortage,
                        'notes' => $submission['notes'] ?? null,
                        'status' => ShiftWaiterBalance::STATUS_BALANCED,
                    ]
                );

                $saved->push($record);
            }

            return $saved;
        });
    }

    public function settleCreditSale(User $cashier, Sale $sale, array $data): Sale
    {
        if (! $sale->is_credit_sale) {
            throw ValidationException::withMessages(['sale' => 'This sale is not a credit tab.']);
        }

        if ($sale->credit_settled_at) {
            throw ValidationException::withMessages(['sale' => 'This tab has already been settled.']);
        }

        return DB::transaction(function () use ($cashier, $sale, $data) {
            $method = $data['settlement_method'];
            $notes = $data['notes'] ?? null;

            $sale->update([
                'credit_settled_at' => now(),
                'credit_settlement_method' => $method,
                'credit_settlement_notes' => $notes,
            ]);

            if ($sale->customer_id) {
                $this->debtLedgerService->recordPayment(
                    $sale->customer,
                    (float) $sale->total,
                    $cashier,
                    'Waiter tab settlement for sale #' . $sale->sale_number
                );
            }

            return $sale->fresh(['customer', 'waiter', 'items']);
        });
    }

    public function attachBalancesToReconciliation(int $businessId, Carbon $date, int $reconciliationId): void
    {
        ShiftWaiterBalance::query()
            ->where('business_id', $businessId)
            ->where('shift_date', $date->toDateString())
            ->where('status', ShiftWaiterBalance::STATUS_BALANCED)
            ->update([
                'end_of_day_reconciliation_id' => $reconciliationId,
                'status' => ShiftWaiterBalance::STATUS_SUBMITTED,
            ]);
    }

    public function balancesForDate(int $businessId, Carbon $date): Collection
    {
        return ShiftWaiterBalance::query()
            ->with('waiter')
            ->where('business_id', $businessId)
            ->where('shift_date', $date->toDateString())
            ->orderBy('waiter_user_id')
            ->get();
    }

    protected function aggregateExpected(Collection $rows): array
    {
        return [
            'expected_cash' => round($rows->sum(fn ($row) => $row['summary']['expected_cash']), 2),
            'expected_mobile_airtel' => round($rows->sum(fn ($row) => $row['summary']['expected_mobile_airtel']), 2),
            'expected_mobile_mtn' => round($rows->sum(fn ($row) => $row['summary']['expected_mobile_mtn']), 2),
            'expected_bank_other' => round($rows->sum(fn ($row) => $row['summary']['expected_bank_other']), 2),
            'expected_credit' => round($rows->sum(fn ($row) => $row['summary']['expected_credit']), 2),
            'shortage' => round((float) $rows->sum(fn ($row) => optional($row['balance'])->shortage ?? 0), 2),
        ];
    }
}
