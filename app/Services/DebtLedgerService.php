<?php

namespace App\Services;

use App\Enums\DebtEntryType;
use App\Helpers\AuditLogger;
use App\Models\Customer;
use App\Models\DebtLedgerEntry;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DebtLedgerService
{
    public function recordDebit(Customer $customer, float $amount, User $user, ?Sale $sale = null, ?string $description = null): DebtLedgerEntry
    {
        return $this->recordEntry($customer, DebtEntryType::DEBIT, $amount, $user, $sale, $description);
    }

    public function recordPayment(Customer $customer, float $amount, User $user, ?string $description = null): DebtLedgerEntry
    {
        return $this->recordEntry($customer, DebtEntryType::PAYMENT, $amount, $user, null, $description ?? 'Debt payment received');
    }

    protected function recordEntry(
        Customer $customer,
        string $type,
        float $amount,
        User $user,
        ?Sale $sale = null,
        ?string $description = null
    ): DebtLedgerEntry {
        return DB::transaction(function () use ($customer, $type, $amount, $user, $sale, $description) {
            $customer = Customer::where('id', $customer->id)->lockForUpdate()->firstOrFail();
            $oldBalance = $customer->outstanding_balance;

            if ($type === DebtEntryType::DEBIT) {
                $newBalance = $oldBalance + $amount;
            } else {
                $newBalance = max(0, $oldBalance - $amount);
            }

            $customer->update(['outstanding_balance' => $newBalance]);

            $entry = DebtLedgerEntry::create([
                'business_id' => $customer->business_id,
                'customer_id' => $customer->id,
                'user_id' => $user->id,
                'sale_id' => optional($sale)->id,
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'description' => $description ?? ucfirst($type) . ' entry',
            ]);

            AuditLogger::record(
                'debt_ledger_' . $type,
                $entry,
                ['outstanding_balance' => $oldBalance],
                ['outstanding_balance' => $newBalance, 'entry' => $entry->toArray()],
                $customer->business_id,
                $user->id
            );

            return $entry;
        });
    }
}
