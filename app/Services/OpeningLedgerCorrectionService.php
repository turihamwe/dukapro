<?php

namespace App\Services;

use App\Enums\DebtEntryType;
use App\Helpers\AuditLogger;
use App\Models\Customer;
use App\Models\DebtLedgerEntry;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierCreditPurchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class OpeningLedgerCorrectionService
{
    /** @var CustomerCreditService */
    protected $customerCredit;

    public function __construct(CustomerCreditService $customerCredit)
    {
        $this->customerCredit = $customerCredit;
    }

    public function correctCustomerOpeningBalance(User $user, Customer $customer, float $newAmount, string $reason): Customer
    {
        $this->assertAllowed($user);

        if ((int) $customer->business_id !== (int) $user->business_id) {
            abort(404);
        }

        $newAmount = round($newAmount, 2);
        $reason = $this->reason($reason);

        return DB::transaction(function () use ($user, $customer, $newAmount, $reason) {
            $customer = Customer::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();

            $entry = DebtLedgerEntry::query()
                ->where('customer_id', $customer->id)
                ->where('is_opening_balance', true)
                ->lockForUpdate()
                ->first();

            $oldAmount = $entry ? round((float) $entry->amount, 2) : round((float) $customer->opening_balance, 2);
            $delta = round($newAmount - $oldAmount, 2);

            if (abs($delta) < 0.009) {
                throw ValidationException::withMessages([
                    'opening_balance' => 'Enter a different opening balance.',
                ]);
            }

            $oldOutstanding = round((float) $customer->outstanding_balance, 2);

            if ($entry) {
                $entry->amount = $newAmount;
                $entry->save();
            } elseif ($newAmount > 0) {
                $entry = DebtLedgerEntry::create([
                    'business_id' => $customer->business_id,
                    'customer_id' => $customer->id,
                    'user_id' => $user->id,
                    'sale_id' => null,
                    'type' => DebtEntryType::DEBIT,
                    'amount' => $newAmount,
                    'balance_after' => 0,
                    'description' => CustomerCreditService::OPENING_BALANCE_DESCRIPTION,
                    'due_date' => null,
                    'is_opening_balance' => true,
                ]);
            }

            $customer->opening_balance = $newAmount;
            $customer->save();

            $this->customerCredit->recalculateOutstandingBalance($customer->fresh());
            $customer = $customer->fresh();

            AuditLogger::record(
                'customer_opening_balance_corrected',
                $customer,
                [
                    'opening_balance' => $oldAmount,
                    'outstanding_balance' => $oldOutstanding,
                ],
                [
                    'opening_balance' => $newAmount,
                    'outstanding_balance' => round((float) $customer->outstanding_balance, 2),
                    'delta' => $delta,
                    'reason' => $reason,
                    'ledger_entry_id' => $entry ? $entry->id : null,
                ],
                (int) $user->business_id,
                (int) $user->id
            );

            return $customer;
        });
    }

    public function correctSupplierOpeningBalance(User $user, Supplier $supplier, float $newAmount, string $reason): Supplier
    {
        $this->assertAllowed($user);

        if ((int) $supplier->business_id !== (int) $user->business_id) {
            abort(404);
        }

        $newAmount = round($newAmount, 2);
        $reason = $this->reason($reason);

        return DB::transaction(function () use ($user, $supplier, $newAmount, $reason) {
            $supplier = Supplier::query()->whereKey($supplier->id)->lockForUpdate()->firstOrFail();

            $purchase = SupplierCreditPurchase::query()
                ->where('supplier_id', $supplier->id)
                ->where('is_opening_balance', true)
                ->lockForUpdate()
                ->first();

            $oldAmount = $purchase ? round((float) $purchase->total_amount, 2) : round((float) $supplier->opening_balance, 2);
            $delta = round($newAmount - $oldAmount, 2);

            if (abs($delta) < 0.009) {
                throw ValidationException::withMessages([
                    'opening_balance' => 'Enter a different opening balance.',
                ]);
            }

            if ($purchase && $newAmount + 0.009 < (float) $purchase->amount_paid) {
                throw ValidationException::withMessages([
                    'opening_balance' => 'Opening balance cannot be lower than the ' . number_format((float) $purchase->amount_paid, 2) . ' already paid on this opening bill.',
                ]);
            }

            if ($purchase) {
                $purchase->total_amount = $newAmount;
                $purchase->save();
                $purchase->refreshPaymentStatus();
            } elseif ($newAmount > 0) {
                $purchase = SupplierCreditPurchase::create([
                    'business_id' => $supplier->business_id,
                    'supplier_id' => $supplier->id,
                    'branch_id' => null,
                    'user_id' => $user->id,
                    'reference' => SupplierCreditService::OPENING_BALANCE_REFERENCE,
                    'purchase_date' => now()->toDateString(),
                    'total_amount' => $newAmount,
                    'amount_paid' => 0,
                    'status' => SupplierCreditPurchase::STATUS_OPEN,
                    'is_opening_balance' => true,
                    'notes' => 'Initial amount owed before using supplier credit in ' . config('app.name', 'DukaPro') . '. No stock was added.',
                ]);
            }

            $supplier->opening_balance = $newAmount;
            $supplier->save();

            AuditLogger::record(
                'supplier_opening_balance_corrected',
                $supplier,
                [
                    'opening_balance' => $oldAmount,
                ],
                [
                    'opening_balance' => $newAmount,
                    'delta' => $delta,
                    'reason' => $reason,
                    'opening_bill_id' => $purchase ? $purchase->id : null,
                    'opening_bill_balance_due' => $purchase ? $purchase->fresh()->balanceDue() : 0,
                ],
                (int) $user->business_id,
                (int) $user->id
            );

            return $supplier->fresh();
        });
    }

    public function correctProductOpeningQuantity(User $user, Product $product, float $newQuantity, string $reason): Product
    {
        $this->assertAllowed($user);

        if ((int) $product->business_id !== (int) $user->business_id) {
            abort(404);
        }

        if ($product->isService() || ! $product->tracksInventory()) {
            throw ValidationException::withMessages([
                'quantity' => 'Services do not have an opening quantity.',
            ]);
        }

        if ($product->isVariableParent()) {
            throw ValidationException::withMessages([
                'quantity' => 'Correct a specific variant. The parent product does not hold stock.',
            ]);
        }

        $newQuantity = round($newQuantity, 3);
        $reason = $this->reason($reason);

        return DB::transaction(function () use ($user, $product, $newQuantity, $reason) {
            $product = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();

            $oldQuantity = round((float) $product->stock_quantity, 3);
            $delta = round($newQuantity - $oldQuantity, 3);

            if (abs($delta) < 0.0005) {
                throw ValidationException::withMessages([
                    'quantity' => 'Enter a different quantity.',
                ]);
            }

            $product->stock_quantity = $newQuantity;
            $product->save();

            AuditLogger::record(
                'product_opening_quantity_corrected',
                $product,
                [
                    'stock_quantity' => $oldQuantity,
                ],
                [
                    'stock_quantity' => $newQuantity,
                    'delta' => $delta,
                    'reason' => $reason,
                ],
                (int) $user->business_id,
                (int) $user->id
            );

            return $product->fresh();
        });
    }

    protected function assertAllowed(User $user): void
    {
        if (! Gate::forUser($user)->allows('adjust-wallet-transactions')) {
            abort(403);
        }
    }

    protected function reason(string $reason): string
    {
        $reason = trim($reason);

        if (mb_strlen($reason) < 3) {
            throw ValidationException::withMessages([
                'reason' => 'Enter a reason for this correction.',
            ]);
        }

        return $reason;
    }
}
