<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\SupplierCreditPayment;
use App\Models\SupplierCreditPurchase;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SupplierCreditOverviewService
{
    public function snapshot(Carbon $start, Carbon $end): array
    {
        $purchasesQuery = SupplierCreditPurchase::query()
            ->whereBetween('purchase_date', [$start->toDateString(), $end->toDateString()]);

        $paymentsQuery = SupplierCreditPayment::query()
            ->whereBetween('paid_at', [$start, $end]);

        return [
            'purchases_count' => (int) (clone $purchasesQuery)->count(),
            'purchases_total' => (float) (clone $purchasesQuery)->sum('total_amount'),
            'payments_count' => (int) (clone $paymentsQuery)->count(),
            'payments_total' => (float) (clone $paymentsQuery)->sum('amount'),
        ];
    }

    public function totalsAllTime(): array
    {
        $openStatuses = [SupplierCreditPurchase::STATUS_OPEN, SupplierCreditPurchase::STATUS_PARTIAL];

        $totalPayable = (float) SupplierCreditPurchase::query()
            ->whereIn('status', $openStatuses)
            ->selectRaw('COALESCE(SUM(total_amount - amount_paid), 0) as total')
            ->value('total');

        return [
            'total_payable' => $totalPayable,
            'open_bills_count' => (int) SupplierCreditPurchase::query()->whereIn('status', $openStatuses)->count(),
            'active_vendors_count' => (int) Supplier::query()->where('is_active', true)->count(),
        ];
    }

    public function timeline(Carbon $start, Carbon $end, int $limit = 50): Collection
    {
        $purchases = SupplierCreditPurchase::query()
            ->with('supplier')
            ->whereBetween('purchase_date', [$start->toDateString(), $end->toDateString()])
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->get();

        $payments = SupplierCreditPayment::query()
            ->with(['supplier', 'purchase'])
            ->whereBetween('paid_at', [$start, $end])
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->get();

        $events = collect();

        foreach ($purchases as $purchase) {
            $events->push([
                'sort_at' => $purchase->created_at,
                'occurred_at' => $purchase->purchase_date->copy()->startOfDay(),
                'type' => 'purchase',
                'label' => 'Bill / stock received on credit',
                'supplier_name' => $purchase->supplier->name ?? 'Vendor',
                'amount' => (float) $purchase->total_amount,
                'purchase' => $purchase,
                'payment' => null,
            ]);
        }

        foreach ($payments as $payment) {
            $events->push([
                'sort_at' => $payment->paid_at,
                'occurred_at' => $payment->paid_at,
                'type' => 'payment',
                'label' => 'Payment to supplier',
                'supplier_name' => $payment->supplier->name ?? 'Vendor',
                'amount' => (float) $payment->amount,
                'purchase' => $payment->purchase,
                'payment' => $payment,
            ]);
        }

        return $events
            ->sortByDesc(fn (array $row) => $row['sort_at']->timestamp)
            ->take($limit)
            ->values();
    }
}
