<?php

namespace App\Services;

use App\Helpers\AuditLogger;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierCreditPayment;
use App\Models\SupplierCreditPurchase;
use App\Models\User;
use App\Support\BatchMode;
use App\Support\SupplierCreditMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierCreditService
{
    /** @var ProductInventoryService */
    protected $inventoryService;

    /** @var ProductBatchService */
    protected $batchService;

    public function __construct(ProductInventoryService $inventoryService, ProductBatchService $batchService)
    {
        $this->inventoryService = $inventoryService;
        $this->batchService = $batchService;
    }

    public function recordCreditPurchase(
        User $user,
        Supplier $supplier,
        Product $stockTarget,
        float $quantity,
        float $unitCost,
        ?string $reference = null,
        ?string $notes = null,
        ?\DateTimeInterface $purchaseDate = null
    ): SupplierCreditPurchase {
        if (! SupplierCreditMode::active($user->business)) {
            throw ValidationException::withMessages([
                'supplier_id' => 'Supplier credit is not enabled for this business.',
            ]);
        }

        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Enter a quantity greater than zero.',
            ]);
        }

        if ($unitCost < 0) {
            throw ValidationException::withMessages([
                'unit_cost' => 'Unit cost cannot be negative.',
            ]);
        }

        if ((int) $supplier->business_id !== (int) $user->business_id) {
            abort(404);
        }

        if ((int) $stockTarget->business_id !== (int) $user->business_id) {
            abort(404);
        }

        if ($stockTarget->isVariableParent()) {
            throw ValidationException::withMessages([
                'product_id' => 'Select a specific variant to restock.',
            ]);
        }

        $lineTotal = round($quantity * $unitCost, 2);
        $business = $user->business;
        $purchaseDate = $purchaseDate ?? now();

        return DB::transaction(function () use (
            $user,
            $supplier,
            $stockTarget,
            $quantity,
            $unitCost,
            $lineTotal,
            $business,
            $reference,
            $notes,
            $purchaseDate
        ) {
            $purchase = SupplierCreditPurchase::create([
                'business_id' => $business->id,
                'supplier_id' => $supplier->id,
                'branch_id' => $stockTarget->branch_id,
                'user_id' => $user->id,
                'reference' => $reference,
                'purchase_date' => $purchaseDate,
                'total_amount' => $lineTotal,
                'amount_paid' => 0,
                'status' => SupplierCreditPurchase::STATUS_OPEN,
                'notes' => $notes,
            ]);

            $purchase->lines()->create([
                'product_id' => $stockTarget->id,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'line_total' => $lineTotal,
                'attribute_values' => $stockTarget->attribute_values,
            ]);

            if (BatchMode::active($business, (int) $stockTarget->branch_id)) {
                $batchData = [
                    'quantity' => $quantity,
                    'selling_price' => (float) $stockTarget->price,
                    'cost_price' => $unitCost,
                ];
                $this->batchService->addBatch($stockTarget, $batchData, (int) $business->id, $user);
            } else {
                $this->inventoryService->topUpStock($stockTarget, $quantity);
            }

            if ($user->can('view-cost-prices') && $unitCost > 0) {
                $stockTarget->update(['cost_price' => $unitCost]);
            }

            AuditLogger::record('supplier_credit_purchase_created', $purchase, null, $purchase->fresh(['lines'])->toArray());

            return $purchase->fresh(['supplier', 'lines.product', 'payments']);
        });
    }

    public function recordPayment(
        User $user,
        SupplierCreditPurchase $purchase,
        float $amount,
        ?string $paymentMethod = null,
        ?string $reference = null,
        ?string $notes = null,
        ?\DateTimeInterface $paidAt = null
    ): SupplierCreditPayment {
        if (! SupplierCreditMode::active($user->business)) {
            throw ValidationException::withMessages([
                'amount' => 'Supplier credit is not enabled for this business.',
            ]);
        }

        if ((int) $purchase->business_id !== (int) $user->business_id) {
            abort(404);
        }

        $amount = round($amount, 2);
        $balance = $purchase->balanceDue();

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Enter an amount greater than zero.',
            ]);
        }

        if ($amount > $balance + 0.009) {
            throw ValidationException::withMessages([
                'amount' => 'Payment exceeds the remaining balance of ' . number_format($balance, 2) . '.',
            ]);
        }

        return DB::transaction(function () use ($user, $purchase, $amount, $paymentMethod, $reference, $notes, $paidAt) {
            $payment = SupplierCreditPayment::create([
                'business_id' => $purchase->business_id,
                'supplier_id' => $purchase->supplier_id,
                'supplier_credit_purchase_id' => $purchase->id,
                'user_id' => $user->id,
                'amount' => $amount,
                'paid_at' => $paidAt ?? now(),
                'payment_method' => $paymentMethod,
                'reference' => $reference,
                'notes' => $notes,
            ]);

            $purchase->amount_paid = round((float) $purchase->amount_paid + $amount, 2);
            $purchase->refreshPaymentStatus();

            AuditLogger::record('supplier_credit_payment_recorded', $payment, null, $payment->toArray());

            return $payment->fresh(['purchase']);
        });
    }
}
