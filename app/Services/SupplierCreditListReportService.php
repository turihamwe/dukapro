<?php

namespace App\Services;

use App\Models\SupplierCreditPayment;
use App\Models\SupplierCreditPurchase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SupplierCreditListReportService
{
    public const MAX_ROWS = 5000;

    public function billsStatusFilter(Request $request): ?string
    {
        $status = $request->input('status');
        $allowed = [
            SupplierCreditPurchase::STATUS_OPEN,
            SupplierCreditPurchase::STATUS_PARTIAL,
            SupplierCreditPurchase::STATUS_PAID,
        ];

        return in_array($status, $allowed, true) ? $status : null;
    }

    public function billsQuery(Request $request): Builder
    {
        $status = $this->billsStatusFilter($request);

        return SupplierCreditPurchase::query()
            ->with(['supplier'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('purchase_date')
            ->orderByDesc('id');
    }

    public function billsForReport(Request $request): Collection
    {
        return $this->billsQuery($request)->limit(self::MAX_ROWS)->get();
    }

    /**
     * @return array{count: int, total_amount: float, total_paid: float, total_outstanding: float}
     */
    public function billsTotals(Collection $purchases): array
    {
        $totalAmount = round((float) $purchases->sum('total_amount'), 2);
        $totalPaid = round((float) $purchases->sum('amount_paid'), 2);

        return [
            'count' => $purchases->count(),
            'total_amount' => $totalAmount,
            'total_paid' => $totalPaid,
            'total_outstanding' => round((float) $purchases->sum(fn ($p) => $p->balanceDue()), 2),
        ];
    }

    public function billsFilterLabel(?string $status): string
    {
        if ($status === SupplierCreditPurchase::STATUS_OPEN) {
            return 'Open bills only';
        }

        if ($status === SupplierCreditPurchase::STATUS_PARTIAL) {
            return 'Partially paid bills';
        }

        if ($status === SupplierCreditPurchase::STATUS_PAID) {
            return 'Paid bills';
        }

        return 'All bills';
    }

    public function paymentsQuery(Request $request): Builder
    {
        return SupplierCreditPayment::query()
            ->with(['supplier', 'purchase', 'user', 'paymentWallet'])
            ->orderByDesc('paid_at')
            ->orderByDesc('id');
    }

    public function paymentsForReport(Request $request): Collection
    {
        return $this->paymentsQuery($request)->limit(self::MAX_ROWS)->get();
    }

    /**
     * @return array{count: int, total_paid: float}
     */
    public function paymentsTotals(Collection $payments): array
    {
        return [
            'count' => $payments->count(),
            'total_paid' => round((float) $payments->sum('amount'), 2),
        ];
    }
}
