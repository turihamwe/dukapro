<?php

namespace App\Services;

use App\Helpers\AuditLogger;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Damage;
use App\Models\DebtLedgerEntry;
use App\Models\EndOfDayReconciliation;
use App\Models\Expense;
use App\Models\HospitalityRoomBooking;
use App\Models\KitchenOrder;
use App\Models\ReconciliationShortage;
use App\Models\Sale;
use App\Models\ShiftWaiterBalance;
use App\Models\ShiftWaiterRoster;
use App\Models\SupplierCreditPayment;
use App\Models\SupplierCreditPurchase;
use App\Models\SupplierCreditPurchaseLine;
use App\Scopes\BranchScope;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TrialDataDeletionService
{
    public function summary(Business $business): array
    {
        $businessId = (int) $business->id;

        return [
            'sales' => (int) Sale::query()
                ->withoutGlobalScope(BranchScope::class)
                ->where('business_id', $businessId)
                ->count(),
            'customers' => (int) Customer::query()->where('business_id', $businessId)->count(),
            'expenses' => (int) Expense::query()->where('business_id', $businessId)->count(),
            'reconciliations' => (int) EndOfDayReconciliation::query()->where('business_id', $businessId)->count(),
        ];
    }

    public function assertBusinessMayDeleteTrialData(Business $business): void
    {
        if (! $business->canDeleteTrialData()) {
            throw new InvalidArgumentException('Trial data can no longer be removed after subscription activation.');
        }
    }

    public function deleteSale(Sale $sale): void
    {
        $this->assertBusinessMayDeleteTrialData($sale->business);

        if ($sale->hasEfrisReceipt()) {
            throw new InvalidArgumentException('This sale has an EFRIS fiscal receipt and cannot be deleted.');
        }

        DB::transaction(function () use ($sale) {
            $this->detachSaleRelations($sale);
            $old = $sale->toArray();
            $sale->delete();
            AuditLogger::record('sale_deleted', $sale, $old, null);
        });
    }

    /**
     * @return array<string, int>
     */
    public function purgeTrialActivity(Business $business): array
    {
        $this->assertBusinessMayDeleteTrialData($business);

        $businessId = (int) $business->id;

        $efrisSales = (int) Sale::query()
            ->withoutGlobalScope(BranchScope::class)
            ->where('business_id', $businessId)
            ->where(function ($query) {
                $query->whereNotNull('efris_fdn')
                    ->orWhereNotNull('efris_qr_code');
            })
            ->count();

        if ($efrisSales > 0) {
            throw new InvalidArgumentException(
                'Some sales have EFRIS fiscal receipts. Contact support before clearing trial data.'
            );
        }

        return DB::transaction(function () use ($business, $businessId) {
            $counts = [
                'sales' => 0,
                'customers' => 0,
                'expenses' => 0,
                'reconciliations' => 0,
                'kitchen_orders' => 0,
                'damages' => 0,
                'supplier_purchases' => 0,
                'bookings' => 0,
            ];

            ReconciliationShortage::query()->where('business_id', $businessId)->delete();
            ShiftWaiterBalance::query()->where('business_id', $businessId)->delete();
            ShiftWaiterRoster::query()->where('business_id', $businessId)->delete();
            $counts['reconciliations'] = EndOfDayReconciliation::query()
                ->where('business_id', $businessId)
                ->delete();

            $paymentIds = SupplierCreditPayment::query()
                ->where('business_id', $businessId)
                ->pluck('id');
            SupplierCreditPayment::query()->whereIn('id', $paymentIds)->delete();

            $purchaseIds = SupplierCreditPurchase::query()
                ->where('business_id', $businessId)
                ->pluck('id');
            SupplierCreditPurchaseLine::query()
                ->whereIn('supplier_credit_purchase_id', $purchaseIds)
                ->delete();
            $counts['supplier_purchases'] = SupplierCreditPurchase::query()
                ->where('business_id', $businessId)
                ->delete();

            $counts['kitchen_orders'] = KitchenOrder::query()->where('business_id', $businessId)->delete();
            $counts['damages'] = Damage::query()->where('business_id', $businessId)->delete();
            DebtLedgerEntry::query()->where('business_id', $businessId)->delete();

            $sales = Sale::query()
                ->withoutGlobalScope(BranchScope::class)
                ->where('business_id', $businessId)
                ->get();

            foreach ($sales as $sale) {
                $this->detachSaleRelations($sale);
                $sale->delete();
                $counts['sales']++;
            }

            $counts['expenses'] = Expense::query()->where('business_id', $businessId)->delete();
            $counts['customers'] = Customer::query()->where('business_id', $businessId)->delete();
            $counts['bookings'] = HospitalityRoomBooking::query()->where('business_id', $businessId)->delete();

            AuditLogger::record(
                'trial_data_purged',
                $business,
                null,
                $counts
            );

            return $counts;
        });
    }

    protected function detachSaleRelations(Sale $sale): void
    {
        DebtLedgerEntry::query()->where('sale_id', $sale->id)->delete();

        KitchenOrder::query()->where('sale_id', $sale->id)->update(['sale_id' => null]);

        if ($sale->kitchen_order_id) {
            KitchenOrder::query()
                ->where('id', $sale->kitchen_order_id)
                ->update(['sale_id' => null]);
        }
    }
}
