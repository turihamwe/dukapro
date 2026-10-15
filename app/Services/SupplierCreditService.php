<?php

namespace App\Services;

use App\Helpers\AuditLogger;
use App\Models\Business;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\PaymentWallet;
use Illuminate\Support\Collection;
use App\Models\SupplierCreditPayment;
use App\Models\SupplierCreditPurchase;
use App\Models\User;
use App\Support\BatchMode;
use App\Support\ProductInventoryValuation;
use App\Support\SupplierCreditMode;
use App\Support\WalletTransactionSource;
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
        return $this->recordCreditPurchaseLines($user, $supplier, [[
            'product_id' => $stockTarget->id,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
        ]], $reference, $notes, $purchaseDate);
    }

    /**
     * Save one supplier delivery as a single bill with many line items.
     *
     * @param  array<int, array{product_id: int|string, quantity: float|string, unit_cost: float|string}>  $lines
     */
    public function recordCreditPurchaseLines(
        User $user,
        Supplier $supplier,
        array $lines,
        ?string $reference = null,
        ?string $notes = null,
        ?\DateTimeInterface $purchaseDate = null
    ): SupplierCreditPurchase {
        if (! SupplierCreditMode::active($user->business)) {
            throw ValidationException::withMessages([
                'supplier_id' => 'Supplier credit is not enabled for this business.',
            ]);
        }

        if ((int) $supplier->business_id !== (int) $user->business_id) {
            abort(404);
        }

        if ($lines === []) {
            throw ValidationException::withMessages([
                'lines' => 'Add at least one product to this bill.',
            ]);
        }

        $business = $user->business;
        $purchaseDate = $purchaseDate ?? now();

        return DB::transaction(function () use ($user, $supplier, $lines, $reference, $notes, $purchaseDate, $business) {
            $lockedSupplier = Supplier::query()->whereKey($supplier->id)->lockForUpdate()->firstOrFail();
            $prepared = $this->preparePurchaseLines($user, $lines);
            $debtBefore = round($lockedSupplier->openBalance(), 2);
            $billNumber = trim((string) $reference) !== ''
                ? trim((string) $reference)
                : $this->generateBillNumber((int) $business->id);

            $purchase = SupplierCreditPurchase::create([
                'business_id' => $business->id,
                'supplier_id' => $lockedSupplier->id,
                'branch_id' => $prepared['branch_id'],
                'user_id' => $user->id,
                'reference' => $billNumber,
                'purchase_date' => $purchaseDate,
                'total_amount' => $prepared['total'],
                'amount_paid' => 0,
                'status' => SupplierCreditPurchase::STATUS_OPEN,
                'notes' => $notes,
            ]);

            $now = now();
            $rows = [];
            foreach ($prepared['lines'] as $line) {
                $rows[] = [
                    'supplier_credit_purchase_id' => $purchase->id,
                    'product_id' => $line['product']->id,
                    'quantity' => $line['quantity'],
                    'unit_cost' => $line['unit_cost'],
                    'line_total' => $line['subtotal'],
                    'attribute_values' => $line['product']->attribute_values
                        ? json_encode($line['product']->attribute_values)
                        : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('supplier_credit_purchase_lines')->insert($rows);

            foreach ($prepared['lines'] as $line) {
                $product = $line['product']->fresh();
                $this->receiveLineIntoStock($user, $purchase, $product, $line['quantity'], $line['unit_cost'], $line['subtotal']);
            }

            $debtAfter = round($lockedSupplier->fresh()->openBalance(), 2);

            AuditLogger::record('supplier_credit_purchase_created', $purchase, null, [
                'bill_number' => $billNumber,
                'total_amount' => $prepared['total'],
                'line_count' => count($prepared['lines']),
                'supplier_debt_before' => $debtBefore,
                'supplier_debt_after' => $debtAfter,
            ]);

            return $purchase->fresh(['supplier', 'lines.product', 'payments']);
        });
    }

    /**
     * @param  array<int, array{product_id: int|string, quantity: float|string, unit_cost: float|string}>  $lines
     * @return array{branch_id: ?int, total: float, lines: array<int, array{product: Product, quantity: float, unit_cost: float, subtotal: float}>}
     */
    protected function preparePurchaseLines(User $user, array $lines): array
    {
        $ids = collect($lines)->pluck('product_id')->map(function ($id) {
            return (int) $id;
        })->filter()->unique()->values()->all();

        $products = Product::query()
            ->where('business_id', $user->business_id)
            ->whereIn('id', $ids)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $prepared = [];
        $errors = [];

        foreach ($lines as $index => $line) {
            $productId = (int) ($line['product_id'] ?? 0);
            $product = $products->get($productId);
            $quantity = round((float) ($line['quantity'] ?? 0), 3);
            $unitCost = round((float) ($line['unit_cost'] ?? 0), 2);

            if (! $product || (int) $product->business_id !== (int) $user->business_id) {
                $errors["lines.{$index}.product_id"] = 'Choose a product for this line.';
                continue;
            }

            if ($product->isVariableParent()) {
                $errors["lines.{$index}.product_id"] = 'Select a specific variant to restock.';
                continue;
            }

            if (! $product->tracksInventory()) {
                $errors["lines.{$index}.product_id"] = 'Services cannot be received into stock.';
                continue;
            }

            if ($quantity <= 0) {
                $errors["lines.{$index}.quantity"] = 'Enter a quantity greater than zero.';
                continue;
            }

            if ($unitCost < 0) {
                $errors["lines.{$index}.unit_cost"] = 'Unit cost cannot be negative.';
                continue;
            }

            $prepared[] = [
                'product' => $product,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'subtotal' => round($quantity * $unitCost, 2),
            ];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $branchIds = collect($prepared)
            ->map(function (array $line) {
                return $line['product']->branch_id ? (int) $line['product']->branch_id : null;
            })
            ->filter()
            ->unique()
            ->values();

        if ($branchIds->count() > 1) {
            throw ValidationException::withMessages([
                'lines' => 'Receive products from one branch on a single bill.',
            ]);
        }

        return [
            'branch_id' => $branchIds->first() ?: \App\Support\BranchContext::id(),
            'total' => round(collect($prepared)->sum('subtotal'), 2),
            'lines' => $prepared,
        ];
    }

    protected function receiveLineIntoStock(
        User $user,
        SupplierCreditPurchase $purchase,
        Product $product,
        float $quantity,
        float $unitCost,
        float $subtotal
    ): void {
        $before = (float) $product->stock_quantity;
        $costUpdates = [];

        if ($user->can('view-cost-prices') && $unitCost > 0) {
            $costUpdates['cost_price'] = $unitCost;
        }

        if (BatchMode::active($user->business, $product->branch_id ? (int) $product->branch_id : null)) {
            $this->batchService->addBatch($product, [
                'quantity' => $quantity,
                'selling_price' => (float) $product->price,
                'cost_price' => $unitCost,
            ], (int) $user->business_id, $user);
        } else {
            if ($user->can('view-cost-prices') && $unitCost > 0) {
                $costUpdates['inventory_cost_price'] = ProductInventoryValuation::weightedAverageAfterReceive(
                    $product,
                    $quantity,
                    $unitCost
                );
            }

            $this->inventoryService->topUpStock($product, $quantity);
        }

        if ($costUpdates !== []) {
            $product->update($costUpdates);
        }

        $product->refresh();

        AuditLogger::record('purchase_received', $product, [
            'stock_quantity' => $before,
        ], [
            'type' => 'purchase_received',
            'bill_id' => $purchase->id,
            'bill_number' => $purchase->reference,
            'supplier_id' => $purchase->supplier_id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'subtotal' => $subtotal,
            'stock_quantity' => (float) $product->stock_quantity,
        ]);
    }

    protected function generateBillNumber(int $businessId): string
    {
        Business::query()->whereKey($businessId)->lockForUpdate()->first();

        $count = SupplierCreditPurchase::withTrashed()
            ->where('business_id', $businessId)
            ->count() + 1;

        return 'BILL-' . str_pad((string) $count, 6, '0', STR_PAD_LEFT);
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
                $this->walletService->withdraw($wallet, $amount, $user, [
                    'source_type' => WalletTransactionSource::VENDOR_PAYMENT,
                    'reference' => $reference,
                    'notes' => $notes ?: ('Bill payment · ' . ($purchase->reference ?: 'Bill #' . $purchase->id)),
                    'occurred_at' => $paidAt,
                ]);
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
     * @return array{
     *     allocations: array<int, array{purchase_id: int, reference: ?string, purchase_date: string, balance_before: float, amount: float, balance_after: float}>,
     *     applied_total: float,
     *     unallocated: float
     * }
     */
    public function planFifoVendorPayment(Collection $openPurchases, float $amount): array
    {
        $remaining = round(max(0, $amount), 2);
        $allocations = [];

        foreach ($openPurchases as $purchase) {
            if ($remaining <= 0) {
                break;
            }

            if (! $purchase instanceof SupplierCreditPurchase) {
                continue;
            }

            $balanceBefore = $purchase->balanceDue();
            if ($balanceBefore <= 0) {
                continue;
            }

            $apply = round(min($remaining, $balanceBefore), 2);
            if ($apply <= 0) {
                continue;
            }

            $allocations[] = [
                'purchase_id' => (int) $purchase->id,
                'reference' => $purchase->reference,
                'purchase_date' => $purchase->purchase_date ? $purchase->purchase_date->toDateString() : '',
                'balance_before' => $balanceBefore,
                'amount' => $apply,
                'balance_after' => round($balanceBefore - $apply, 2),
            ];

            $remaining = round($remaining - $apply, 2);
        }

        $applied = round($amount - $remaining, 2);

        return [
            'allocations' => $allocations,
            'applied_total' => $applied,
            'unallocated' => $remaining,
        ];
    }

    /**
     * @return array{payments: Collection<int, SupplierCreditPayment>, plan: array<string, mixed>}
     */
    public function settleVendorDebtFifo(
        User $user,
        Supplier $supplier,
        float $amount,
        ?string $paymentMethod = null,
        ?string $reference = null,
        ?string $notes = null,
        ?\DateTimeInterface $paidAt = null,
        ?PaymentWallet $wallet = null
    ): array {
        if (! SupplierCreditMode::active($user->business)) {
            throw ValidationException::withMessages([
                'amount' => 'Supplier credit is not enabled for this business.',
            ]);
        }

        if ((int) $supplier->business_id !== (int) $user->business_id) {
            abort(404);
        }

        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Enter an amount greater than zero.',
            ]);
        }

        $openBalance = round($supplier->openBalance(), 2);

        if ($amount > $openBalance + 0.009) {
            throw ValidationException::withMessages([
                'amount' => 'Payment exceeds this vendor\'s open balance of ' . number_format($openBalance, 2) . '.',
            ]);
        }

        return DB::transaction(function () use ($user, $supplier, $amount, $paymentMethod, $reference, $notes, $paidAt, $wallet) {
            $purchases = $supplier->openPurchasesFifo()->lockForUpdate()->get();

            $plan = $this->planFifoVendorPayment($purchases, $amount);

            if ($plan['applied_total'] <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'This vendor has no open bills to pay.',
                ]);
            }

            if ($plan['unallocated'] > 0.009) {
                throw ValidationException::withMessages([
                    'amount' => 'Could not allocate the full payment across open bills. Try again or pay a lower amount.',
                ]);
            }

            if ($wallet) {
                $this->walletService->withdraw($wallet, $plan['applied_total'], $user, [
                    'source_type' => WalletTransactionSource::VENDOR_PAYMENT,
                    'reference' => $reference,
                    'notes' => $notes ?: ('Vendor settlement · ' . $supplier->name),
                    'occurred_at' => $paidAt,
                ]);
            }

            $payments = collect();
            $paidAtValue = $paidAt ?? now();

            foreach ($plan['allocations'] as $row) {
                /** @var SupplierCreditPurchase|null $purchase */
                $purchase = $purchases->firstWhere('id', $row['purchase_id']);

                if (! $purchase) {
                    continue;
                }

                $slice = (float) $row['amount'];

                $payment = SupplierCreditPayment::create([
                    'business_id' => $purchase->business_id,
                    'supplier_id' => $supplier->id,
                    'supplier_credit_purchase_id' => $purchase->id,
                    'user_id' => $user->id,
                    'amount' => $slice,
                    'paid_at' => $paidAtValue,
                    'payment_method' => $paymentMethod,
                    'reference' => $reference,
                    'notes' => $notes,
                    'payment_wallet_id' => $wallet ? $wallet->id : null,
                ]);

                $purchase->amount_paid = round((float) $purchase->amount_paid + $slice, 2);
                $purchase->refreshPaymentStatus();

                AuditLogger::record('supplier_credit_payment_recorded', $payment, null, $payment->toArray());

                $payments->push($payment);
            }

            AuditLogger::record(
                'supplier_vendor_debt_settled',
                $supplier,
                null,
                [
                    'supplier_id' => $supplier->id,
                    'total_amount' => $plan['applied_total'],
                    'allocations' => $plan['allocations'],
                    'payment_ids' => $payments->pluck('id')->all(),
                ],
                (int) $user->business_id,
                (int) $user->id
            );

            return [
                'payments' => $payments,
                'plan' => $plan,
            ];
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
     * Manual vendor bill (no stock). Increases payables immediately; purchase_date drives statement ordering.
     */
    public function recordManualVendorBill(
        User $user,
        Supplier $supplier,
        float $amount,
        \DateTimeInterface $purchaseDate,
        bool $isHistoricalAdjustment = false,
        ?string $reference = null,
        ?string $notes = null
    ): SupplierCreditPurchase {
        if (! SupplierCreditMode::active($user->business)) {
            throw ValidationException::withMessages([
                'amount' => 'Supplier credit is not enabled for this business.',
            ]);
        }

        if ((int) $supplier->business_id !== (int) $user->business_id) {
            abort(404);
        }

        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Enter a bill amount greater than zero.',
            ]);
        }

        return DB::transaction(function () use ($user, $supplier, $amount, $purchaseDate, $isHistoricalAdjustment, $reference, $notes) {
            $purchase = SupplierCreditPurchase::create([
                'business_id' => $supplier->business_id,
                'supplier_id' => $supplier->id,
                'branch_id' => null,
                'user_id' => $user->id,
                'reference' => $reference,
                'purchase_date' => $purchaseDate,
                'total_amount' => $amount,
                'amount_paid' => 0,
                'status' => SupplierCreditPurchase::STATUS_OPEN,
                'is_opening_balance' => false,
                'is_historical_adjustment' => $isHistoricalAdjustment,
                'notes' => $notes,
            ]);

            AuditLogger::record('supplier_manual_bill_created', $purchase, null, $purchase->fresh()->toArray());

            return $purchase->fresh(['supplier']);
        });
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
            $this->walletService->deposit($wallet, (float) $payment->amount, $user, [
                'source_type' => WalletTransactionSource::VENDOR_PAYMENT_REVERSAL,
                'reference' => $payment->reference,
                'notes' => $payment->notes ?: 'Vendor payment reversed',
                'occurred_at' => $payment->paid_at,
            ]);
        }
    }
}
