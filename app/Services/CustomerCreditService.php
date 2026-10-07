<?php

namespace App\Services;

use App\Enums\DebtEntryType;
use App\Helpers\AuditLogger;
use App\Models\Customer;
use App\Models\DebtLedgerEntry;
use App\Models\Sale;
use App\Models\User;
use App\Support\CustomerCreditMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerCreditService
{
    public const OPENING_BALANCE_DESCRIPTION = 'Opening Balance';

    /** @var DebtLedgerService */
    protected $debtLedgerService;

    /** @var PaymentWalletService */
    protected $walletService;

    public function __construct(DebtLedgerService $debtLedgerService, PaymentWalletService $walletService)
    {
        $this->debtLedgerService = $debtLedgerService;
        $this->walletService = $walletService;
    }

    /**
     * Record opening balance as a ledger debit with no sale and no inventory impact.
     */
    public function ensureOpeningBalanceEntry(User $user, Customer $customer, float $amount): ?DebtLedgerEntry
    {
        if (! CustomerCreditMode::active($user->business)) {
            return null;
        }

        if ((int) $customer->business_id !== (int) $user->business_id) {
            abort(404);
        }

        $amount = round($amount, 2);

        if ($amount <= 0) {
            return null;
        }

        if ($customer->hasOpeningBalanceEntry()) {
            throw ValidationException::withMessages([
                'opening_balance' => 'This customer already has an opening balance entry.',
            ]);
        }

        return DB::transaction(function () use ($user, $customer, $amount) {
            $entry = $this->debtLedgerService->recordDebit(
                $customer,
                $amount,
                $user,
                null,
                self::OPENING_BALANCE_DESCRIPTION,
                null
            );

            $entry->update(['is_opening_balance' => true]);

            AuditLogger::record('customer_opening_balance_created', $entry, null, $entry->fresh()->toArray());

            return $entry->fresh();
        });
    }

    /**
     * Soft-delete a credit sale invoice. Does not restore inventory.
     */
    public function softDeleteInvoice(Sale $sale, User $user): void
    {
        if ((int) $sale->business_id !== (int) $user->business_id) {
            abort(404);
        }

        if (! $sale->is_credit_sale) {
            throw ValidationException::withMessages([
                'sale' => 'Only credit sales can be removed from receivables this way.',
            ]);
        }

        if ($sale->hasEfrisReceipt()) {
            throw ValidationException::withMessages([
                'sale' => 'This sale has an EFRIS fiscal receipt and cannot be deleted.',
            ]);
        }

        DB::transaction(function () use ($sale, $user) {
            $oldSale = $sale->toArray();
            $customerId = $sale->customer_id;

            DebtLedgerEntry::query()
                ->where('sale_id', $sale->id)
                ->each(function (DebtLedgerEntry $entry) use ($user) {
                    $old = $entry->toArray();
                    $entry->delete();
                    AuditLogger::record('debt_ledger_entry_deleted', $entry, $old, null, (int) $user->business_id, (int) $user->id);
                });

            $sale->delete();

            AuditLogger::record('customer_credit_invoice_deleted', $sale, $oldSale, null, (int) $user->business_id, (int) $user->id);

            if ($customerId) {
                $customer = Customer::query()->find($customerId);
                if ($customer) {
                    $this->recalculateOutstandingBalance($customer);
                }
            }
        });
    }

    public function softDeletePayment(DebtLedgerEntry $entry, User $user): void
    {
        if ((int) $entry->business_id !== (int) $user->business_id) {
            abort(404);
        }

        if ($entry->type !== DebtEntryType::PAYMENT) {
            throw ValidationException::withMessages([
                'payment' => 'Only payment entries can be removed here.',
            ]);
        }

        DB::transaction(function () use ($entry, $user) {
            $old = $entry->toArray();
            $customer = $entry->customer;

            $this->reverseWalletForDeletedPayment($entry, $user);

            $entry->delete();

            AuditLogger::record('customer_credit_payment_deleted', $entry, $old, null, (int) $user->business_id, (int) $user->id);

            if ($customer) {
                $this->recalculateOutstandingBalance($customer);
            }
        });
    }

    /**
     * Soft-delete customer and hide linked ledger entries and credit sales. Does not change stock.
     */
    public function softDeleteCustomer(Customer $customer, User $user): void
    {
        if ((int) $customer->business_id !== (int) $user->business_id) {
            abort(404);
        }

        DB::transaction(function () use ($customer, $user) {
            $old = $customer->toArray();

            Sale::query()
                ->where('customer_id', $customer->id)
                ->where('is_credit_sale', true)
                ->orderBy('id')
                ->each(function (Sale $sale) use ($user) {
                    if ($sale->trashed() || $sale->hasEfrisReceipt()) {
                        return;
                    }

                    $oldSale = $sale->toArray();

                    DebtLedgerEntry::query()
                        ->where('sale_id', $sale->id)
                        ->each(function (DebtLedgerEntry $entry) use ($user) {
                            $entryOld = $entry->toArray();
                            $entry->delete();
                            AuditLogger::record('debt_ledger_entry_deleted', $entry, $entryOld, null, (int) $user->business_id, (int) $user->id);
                        });

                    $sale->delete();
                    AuditLogger::record('customer_credit_invoice_deleted', $sale, $oldSale, null, (int) $user->business_id, (int) $user->id);
                });

            DebtLedgerEntry::query()
                ->where('customer_id', $customer->id)
                ->orderBy('id')
                ->each(function (DebtLedgerEntry $entry) use ($user) {
                    if ($entry->trashed()) {
                        return;
                    }
                    $entryOld = $entry->toArray();
                    $entry->delete();
                    AuditLogger::record('debt_ledger_entry_deleted', $entry, $entryOld, null, (int) $user->business_id, (int) $user->id);
                });

            $customer->delete();

            AuditLogger::record('customer_credit_customer_deleted', $customer, $old, null, (int) $user->business_id, (int) $user->id);
        });
    }

    public function recalculateOutstandingBalance(Customer $customer): void
    {
        $balance = 0.0;

        DebtLedgerEntry::query()
            ->where('customer_id', $customer->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->each(function (DebtLedgerEntry $entry) use (&$balance) {
                if ($entry->type === DebtEntryType::DEBIT) {
                    $balance += (float) $entry->amount;
                } elseif ($entry->type === DebtEntryType::PAYMENT) {
                    $balance = max(0, $balance - (float) $entry->amount);
                }
            });

        $customer->update(['outstanding_balance' => round($balance, 2)]);
    }

    protected function reverseWalletForDeletedPayment(DebtLedgerEntry $entry, User $user): void
    {
        if (! $entry->payment_wallet_id) {
            return;
        }

        $entry->loadMissing('paymentWallet');
        $wallet = $entry->paymentWallet;

        if ($wallet) {
            $this->walletService->withdraw($wallet, (float) $entry->amount, $user);
        }
    }
}
