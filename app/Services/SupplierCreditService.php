<?php

namespace App\Services;

use App\Helpers\AuditLogger;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\PaymentWallet;
use App\Models\SupplierCreditPayment;
use App\Models\SupplierCreditPurchase;
use App\Models\User;
use App\Support\BatchMode;
use App\Support\ProductInventoryValuation;
use App\Support\SupplierCreditMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierCreditService
{
    public const OPENING_BALANCE_REFERENCE = 'Opening Balance';

    /** @var ProductInventoryService */
    protected $inventoryService;

    /** @var ProductBatchService */
    protected $batchService;

    /** @var PaymentWalletService */
    protected $walletService;

    public function __construct(
        ProductInventoryService $inventoryService,
        ProductBatchService $batchService,
        PaymentWalletService $walletService
    ) {
        $this->inventoryService = $inventoryService;
        $this->batchService = $batchService;
        $this->walletService = $walletService;
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

            $costUpdates = [];

            if ($user->can('view-cost-prices') && $unitCost > 0) {
                $costUpdates['cost_price'] = $unitCost;
            }

            if (BatchMode::active($business, (int) $stockTarget->branch_id)) {
                $batchData = [
                    'quantity' => $quantity,
                    'selling_price' => (float) $stockTarget->price,
                    'cost_price' => $unitCost,
                ];
                $this->batchService->addBatch($stockTarget, $batchData, (int) $business->id, $user);
            } else {
                if ($user->can('view-cost-prices') && $unitCost > 0) {
                    $costUpdates['inventory_cost_price'] = ProductInventoryValuation::weightedAverageAfterReceive(
                        $stockTarget,
                        $quantity,
                        $unitCost
                    );
                }

                $this->inventoryService->topUpStock($stockTarget, $quantity);
            }

            if ($costUpdates !== []) {
                $stockTarget->update($costUpdates);
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
        ?\DateTimeInterface $paidAt = null,
        ?PaymentWallet $wallet = null
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

        return DB::transaction(function () use ($user, $purchase, $amount, $paymentMethod, $reference, $notes, $paidAt, $wallet) {
            if ($wallet) {
                $this->walletService->withdraw($wallet, $amount, $user);
            }

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
                'payment_wallet_id' => $wallet ? $wallet->id : null,
            ]);

            $purchase->amount_paid = round((float) $purchase->amount_paid + $amount, 2);
            $purchase->refreshPaymentStatus();

            AuditLogger::record('supplier_credit_payment_recorded', $payment, null, $payment->toArray());

            return $payment->fresh(['purchase']);
        });
    }

    /**
     * Soft-delete a bill and its payments. Does not change inventory.
     */
    public function softDeleteBill(SupplierCreditPurchase $purchase, User $user): void
    {
        if ((int) $purchase->business_id !== (int) $user->business_id) {
            abort(404);
        }

        DB::transaction(function () use ($purchase, $user) {
            $old = $purchase->toArray();
            $purchase->loadMissing('payments');

            foreach ($purchase->payments as $payment) {
                $this->restoreWalletForDeletedPayment($payment, $user);
                $payment->delete();
            }

            $purchase->delete();

            AuditLogger::record('supplier_credit_purchase_deleted', $purchase, $old, null, (int) $user->business_id, (int) $user->id);
        });
    }

    /**
     * Soft-delete a payment and recalculate the linked bill balance from remaining payments.
     */
    public function softDeletePayment(SupplierCreditPayment $payment, User $user): void
    {
        if ((int) $payment->business_id !== (int) $user->business_id) {
            abort(404);
        }

        DB::transaction(function () use ($payment, $user) {
            $old = $payment->toArray();
            $purchase = $payment->purchase;

            $this->restoreWalletForDeletedPayment($payment, $user);

            $payment->delete();

            if ($purchase && ! $purchase->trashed()) {
                $this->syncPurchasePaidAmountFromPayments($purchase);
            }

            AuditLogger::record('supplier_credit_payment_deleted', $payment, $old, null, (int) $user->business_id, (int) $user->id);
        });
    }

    /**
     * Soft-delete vendor and hide linked bills/payments. Does not change inventory.
     */
    public function softDeleteVendor(Supplier $supplier, User $user): void
    {
        if ((int) $supplier->business_id !== (int) $user->business_id) {
            abort(404);
        }

        DB::transaction(function () use ($supplier, $user) {
            $old = $supplier->toArray();

            $supplier->creditPurchases()
                ->orderBy('id')
                ->each(function (SupplierCreditPurchase $purchase) use ($user) {
                    $this->softDeleteBill($purchase, $user);
                });

            $supplier->delete();

            AuditLogger::record('supplier_deleted', $supplier, $old, null, (int) $user->business_id, (int) $user->id);
        });
    }

    public function syncPurchasePaidAmountFromPayments(SupplierCreditPurchase $purchase): void
    {
        $paid = (float) $purchase->payments()->sum('amount');
        $purchase->amount_paid = round($paid, 2);
        $purchase->refreshPaymentStatus();
    }

    /**
     * Create a payable opening balance bill with no inventory movement.
     */
    public function ensureOpeningBalanceBill(User $user, Supplier $supplier, float $amount): ?SupplierCreditPurchase
    {
        if (! SupplierCreditMode::active($user->business)) {
            return null;
        }

        if ((int) $supplier->business_id !== (int) $user->business_id) {
            abort(404);
        }

        $amount = round($amount, 2);

        if ($amount <= 0) {
            return null;
        }

        if ($supplier->hasOpeningBalanceBill()) {
            throw ValidationException::withMessages([
                'opening_balance' => 'This vendor already has an opening balance bill. Pay it down or remove it before changing the opening amount.',
            ]);
        }

        return DB::transaction(function () use ($user, $supplier, $amount) {
            $purchase = SupplierCreditPurchase::create([
                'business_id' => $supplier->business_id,
                'supplier_id' => $supplier->id,
                'branch_id' => null,
                'user_id' => $user->id,
                'reference' => self::OPENING_BALANCE_REFERENCE,
                'purchase_date' => now()->toDateString(),
                'total_amount' => $amount,
                'amount_paid' => 0,
                'status' => SupplierCreditPurchase::STATUS_OPEN,
                'is_opening_balance' => true,
                'notes' => 'Initial amount owed before using supplier credit in ' . config('app.name', 'DukaPro') . '. No stock was added.',
            ]);

            AuditLogger::record('supplier_opening_balance_bill_created', $purchase, null, $purchase->toArray());

            return $purchase;
        });
    }

    protected function restoreWalletForDeletedPayment(SupplierCreditPayment $payment, User $user): void
    {
        if (! $payment->payment_wallet_id) {
            return;
        }

        $payment->loadMissing('paymentWallet');
        $wallet = $payment->paymentWallet;

        if ($wallet) {
            $this->walletService->deposit($wallet, (float) $payment->amount, $user);
        }
    }
}
