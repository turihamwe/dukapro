<?php

namespace App\Services;

use App\Helpers\AuditLogger;
use App\Models\Business;
use App\Models\PaymentWallet;
use App\Models\PaymentWalletTransaction;
use App\Models\User;
use App\Support\PaymentWalletMode;
use App\Support\WalletInflowCategory;
use App\Support\WalletTransactionSource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PaymentWalletService
{
    public function activeForBusiness(int $businessId)
    {
        $business = Business::query()->find($businessId);

        if (! PaymentWalletMode::active($business)) {
            return collect();
        }

        return PaymentWallet::query()
            ->where('business_id', $businessId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function summaryForBusiness(int $businessId): array
    {
        $business = Business::query()->find($businessId);

        if (! PaymentWalletMode::active($business)) {
            return [
                'wallets' => collect(),
                'total_liquid' => 0.0,
            ];
        }

        $wallets = PaymentWallet::query()
            ->where('business_id', $businessId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return [
            'wallets' => $wallets,
            'total_liquid' => round((float) $wallets->sum('current_balance'), 2),
        ];
    }

    public function resolveForBusiness(int $businessId, ?int $walletId): ?PaymentWallet
    {
        $business = Business::query()->find($businessId);

        if (! PaymentWalletMode::active($business)) {
            if ($walletId) {
                throw ValidationException::withMessages([
                    'payment_wallet_id' => 'Payment wallets are not enabled for this business.',
                ]);
            }

            return null;
        }

        if (! $walletId) {
            return null;
        }

        $wallet = PaymentWallet::query()
            ->where('business_id', $businessId)
            ->whereKey($walletId)
            ->first();

        if (! $wallet || ! $wallet->is_active) {
            throw ValidationException::withMessages([
                'payment_wallet_id' => 'Select a valid active wallet.',
            ]);
        }

        return $wallet;
    }

    public function assertWalletRequired(int $businessId, ?int $walletId): void
    {
        $business = Business::query()->find($businessId);

        if (! PaymentWalletMode::active($business)) {
            return;
        }

        $hasWallets = PaymentWallet::query()
            ->where('business_id', $businessId)
            ->where('is_active', true)
            ->exists();

        if ($hasWallets && ! $walletId) {
            throw ValidationException::withMessages([
                'payment_wallet_id' => 'Select which account receives or pays this amount.',
            ]);
        }
    }

    /**
     * @param  array{source_type?: string, category?: string, source_name?: string, notes?: string, reference?: string, occurred_at?: \DateTimeInterface}  $context
     */
    public function deposit(PaymentWallet $wallet, float $amount, User $user, array $context = []): PaymentWallet
    {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Deposit amount must be greater than zero.',
            ]);
        }

        if ((int) $wallet->business_id !== (int) $user->business_id) {
            abort(404);
        }

        return DB::transaction(function () use ($wallet, $amount, $user, $context) {
            $locked = PaymentWallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();
            $oldBalance = (float) $locked->current_balance;
            $locked->current_balance = round($oldBalance + $amount, 2);
            $locked->save();
            $fresh = $locked->fresh();

            $this->createLedgerEntry(
                $fresh,
                $user,
                PaymentWalletTransaction::DIRECTION_IN,
                $amount,
                (float) $fresh->current_balance,
                array_merge([
                    'source_type' => WalletTransactionSource::ADJUSTMENT_IN,
                    'category' => WalletInflowCategory::MISCELLANEOUS,
                ], $context)
            );

            AuditLogger::record(
                'payment_wallet_deposit',
                $fresh,
                ['current_balance' => $oldBalance],
                ['current_balance' => $fresh->current_balance, 'amount' => $amount],
                (int) $user->business_id,
                (int) $user->id
            );

            return $fresh;
        });
    }

    /**
     * @param  array{source_type?: string, category?: string, source_name?: string, notes?: string, reference?: string, occurred_at?: \DateTimeInterface}  $context
     */
    public function withdraw(PaymentWallet $wallet, float $amount, User $user, array $context = []): PaymentWallet
    {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Withdrawal amount must be greater than zero.',
            ]);
        }

        if ((int) $wallet->business_id !== (int) $user->business_id) {
            abort(404);
        }

        return DB::transaction(function () use ($wallet, $amount, $user, $context) {
            $locked = PaymentWallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            if ((float) $locked->current_balance + 0.009 < $amount) {
                throw ValidationException::withMessages([
                    'payment_wallet_id' => 'Insufficient balance in ' . $locked->name . ' (available ' . number_format((float) $locked->current_balance, 2) . ').',
                ]);
            }

            $oldBalance = (float) $locked->current_balance;
            $locked->current_balance = round($oldBalance - $amount, 2);
            $locked->save();
            $fresh = $locked->fresh();

            $this->createLedgerEntry(
                $fresh,
                $user,
                PaymentWalletTransaction::DIRECTION_OUT,
                $amount,
                (float) $fresh->current_balance,
                array_merge([
                    'source_type' => WalletTransactionSource::ADJUSTMENT_OUT,
                    'category' => WalletTransactionSource::ADJUSTMENT_OUT,
                ], $context)
            );

            AuditLogger::record(
                'payment_wallet_withdraw',
                $fresh,
                ['current_balance' => $oldBalance],
                ['current_balance' => $fresh->current_balance, 'amount' => $amount],
                (int) $user->business_id,
                (int) $user->id
            );

            return $fresh;
        });
    }

    public function recordManualInflow(
        User $user,
        PaymentWallet $wallet,
        float $amount,
        string $category,
        ?string $sourceName = null,
        ?\DateTimeInterface $occurredAt = null
    ): PaymentWalletTransaction {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Deposit amount must be greater than zero.',
            ]);
        }

        if ((int) $wallet->business_id !== (int) $user->business_id) {
            abort(404);
        }

        if (! array_key_exists($category, WalletInflowCategory::all())) {
            throw ValidationException::withMessages([
                'category' => 'Select a valid inflow category.',
            ]);
        }

        return DB::transaction(function () use ($user, $wallet, $amount, $category, $sourceName, $occurredAt) {
            $locked = PaymentWallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();
            $oldBalance = (float) $locked->current_balance;
            $locked->current_balance = round($oldBalance + $amount, 2);
            $locked->save();

            $transaction = $this->createLedgerEntry(
                $locked,
                $user,
                PaymentWalletTransaction::DIRECTION_IN,
                $amount,
                (float) $locked->current_balance,
                [
                    'source_type' => WalletTransactionSource::MANUAL_DEPOSIT,
                    'category' => $category,
                    'source_name' => $sourceName,
                    'notes' => $sourceName,
                    'occurred_at' => $occurredAt ?? now(),
                ]
            );

            AuditLogger::record(
                'payment_wallet_manual_inflow',
                $transaction,
                ['wallet_balance' => $oldBalance],
                [
                    'wallet_balance' => (float) $locked->current_balance,
                    'transaction' => $transaction->toArray(),
                ],
                (int) $user->business_id,
                (int) $user->id
            );

            return $transaction->fresh(['wallet', 'user']);
        });
    }

    public function create(User $user, array $data): PaymentWallet
    {
        $opening = round((float) ($data['opening_balance'] ?? 0), 2);

        $wallet = PaymentWallet::create([
            'business_id' => (int) $user->business_id,
            'name' => $data['name'],
            'type' => $data['type'] ?? PaymentWallet::TYPE_CASH,
            'opening_balance' => $opening,
            'current_balance' => $opening,
            'is_active' => true,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        AuditLogger::record('payment_wallet_created', $wallet, null, $wallet->toArray(), (int) $user->business_id, (int) $user->id);

        return $wallet;
    }

    public function archive(User $user, PaymentWallet $wallet): void
    {
        Gate::authorize('delete-payment-wallets');

        if ((int) $wallet->business_id !== (int) $user->business_id) {
            abort(404);
        }

        $balance = round((float) $wallet->current_balance, 2);

        if ($balance > 0 || $balance < 0) {
            throw ValidationException::withMessages([
                'wallet' => 'Cannot delete a wallet with an active balance. Please clear or transfer the remaining funds first.',
            ]);
        }

        $old = $wallet->toArray();

        $wallet->forceFill(['is_active' => false])->save();
        $wallet->delete();

        AuditLogger::record(
            'payment_wallet_archived',
            $wallet,
            $old,
            array_merge($old, ['deleted_at' => $wallet->deleted_at]),
            (int) $user->business_id,
            (int) $user->id
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function transactionHistory(PaymentWallet $wallet, int $limit = 50): array
    {
        $limit = max(1, min(50, $limit));

        return PaymentWalletTransaction::query()
            ->where('payment_wallet_id', $wallet->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function (PaymentWalletTransaction $transaction) {
                return [
                    'id' => $transaction->id,
                    'occurred_at' => $transaction->occurred_at
                        ? $transaction->occurred_at->format('M j, Y g:i A')
                        : '—',
                    'direction' => $transaction->direction,
                    'direction_label' => $transaction->isInflow() ? 'Inflow' : 'Outflow',
                    'source_label' => $transaction->sourceLabel(),
                    'notes' => $transaction->displayNotes() ?: '—',
                    'notes_raw' => $transaction->notes ?? $transaction->source_name ?? '',
                    'reference' => $transaction->reference,
                    'reference_raw' => $transaction->reference ?? '',
                    'amount' => (float) $transaction->amount,
                    'balance_after' => $transaction->balance_after !== null ? (float) $transaction->balance_after : null,
                    'category' => $transaction->category,
                    'source_type' => $transaction->source_type,
                    'source_name' => $transaction->source_name,
                ];
            })
            ->all();
    }

    public function updateTransaction(User $user, PaymentWalletTransaction $transaction, array $data): PaymentWalletTransaction
    {
        if (! Gate::forUser($user)->allows('adjust-wallet-transactions')) {
            abort(403);
        }

        if ((int) $transaction->business_id !== (int) $user->business_id) {
            abort(404);
        }

        $newAmount = round((float) ($data['amount'] ?? 0), 2);

        if ($newAmount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Enter an amount greater than zero.',
            ]);
        }

        return DB::transaction(function () use ($user, $transaction, $data, $newAmount) {
            $transaction = PaymentWalletTransaction::query()->whereKey($transaction->id)->lockForUpdate()->firstOrFail();

            $wallet = PaymentWallet::query()->whereKey($transaction->payment_wallet_id)->lockForUpdate()->firstOrFail();

            $oldAmount = (float) $transaction->amount;
            $delta = round($newAmount - $oldAmount, 2);

            if (abs($delta) > 0.009) {
                $balance = (float) $wallet->current_balance;

                if ($transaction->isInflow()) {
                    $balance = round($balance + $delta, 2);
                } else {
                    $balance = round($balance - $delta, 2);
                }

                if ($balance < -0.009) {
                    throw ValidationException::withMessages([
                        'amount' => 'This change would leave the wallet with a negative balance.',
                    ]);
                }

                $wallet->current_balance = $balance;
                $wallet->save();
            }

            $updates = [
                'amount' => $newAmount,
                'reference' => isset($data['reference']) ? ($data['reference'] !== '' ? $data['reference'] : null) : $transaction->reference,
                'notes' => isset($data['notes']) ? ($data['notes'] !== '' ? $data['notes'] : null) : $transaction->notes,
            ];

            if ($transaction->isInflow() && ! empty($data['category'])) {
                if (! array_key_exists($data['category'], WalletInflowCategory::all())) {
                    throw ValidationException::withMessages([
                        'category' => 'Select a valid inflow category.',
                    ]);
                }

                $updates['category'] = $data['category'];
            }

            if ($transaction->source_type === WalletTransactionSource::MANUAL_DEPOSIT && array_key_exists('notes', $data)) {
                $updates['source_name'] = $updates['notes'];
            }

            $before = $transaction->toArray();
            $transaction->update($updates);

            $this->refreshLedgerRunningBalances($wallet->fresh());

            AuditLogger::record(
                'payment_wallet_transaction_updated',
                $transaction,
                $before,
                $transaction->fresh()->toArray(),
                (int) $user->business_id,
                (int) $user->id
            );

            return $transaction->fresh();
        });
    }

    public function deleteTransaction(User $user, PaymentWalletTransaction $transaction): void
    {
        if (! Gate::forUser($user)->allows('adjust-wallet-transactions')) {
            abort(403);
        }

        if ((int) $transaction->business_id !== (int) $user->business_id) {
            abort(404);
        }

        DB::transaction(function () use ($user, $transaction) {
            $transaction = PaymentWalletTransaction::query()->whereKey($transaction->id)->lockForUpdate()->firstOrFail();

            $wallet = PaymentWallet::query()->whereKey($transaction->payment_wallet_id)->lockForUpdate()->firstOrFail();

            $amount = (float) $transaction->amount;
            $balance = (float) $wallet->current_balance;

            if ($transaction->isInflow()) {
                $balance = round($balance - $amount, 2);
            } else {
                $balance = round($balance + $amount, 2);
            }

            if ($balance < -0.009) {
                throw ValidationException::withMessages([
                    'transaction' => 'Removing this entry would leave the wallet with a negative balance.',
                ]);
            }

            $before = $transaction->toArray();

            $wallet->current_balance = $balance;
            $wallet->save();

            $transaction->delete();

            $this->refreshLedgerRunningBalances($wallet->fresh());

            AuditLogger::record(
                'payment_wallet_transaction_deleted',
                $transaction,
                $before,
                ['deleted_at' => now()->toIso8601String()],
                (int) $user->business_id,
                (int) $user->id
            );
        });
    }

    protected function refreshLedgerRunningBalances(PaymentWallet $wallet): void
    {
        $running = round((float) $wallet->opening_balance, 2);

        PaymentWalletTransaction::query()
            ->where('payment_wallet_id', $wallet->id)
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->each(function (PaymentWalletTransaction $entry) use (&$running) {
                if ($entry->isInflow()) {
                    $running = round($running + (float) $entry->amount, 2);
                } else {
                    $running = round($running - (float) $entry->amount, 2);
                }

                $stored = $entry->balance_after !== null ? round((float) $entry->balance_after, 2) : null;

                if ($stored !== $running) {
                    $entry->forceFill(['balance_after' => $running])->saveQuietly();
                }
            });
    }

    /**
     * @param  array{source_type?: string, category?: string, source_name?: string, notes?: string, reference?: string, occurred_at?: \DateTimeInterface}  $context
     */
    protected function createLedgerEntry(
        PaymentWallet $wallet,
        User $user,
        string $direction,
        float $amount,
        float $balanceAfter,
        array $context
    ): PaymentWalletTransaction {
        $category = $context['category'] ?? WalletInflowCategory::MISCELLANEOUS;

        return PaymentWalletTransaction::create([
            'business_id' => (int) $wallet->business_id,
            'payment_wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'direction' => $direction,
            'source_type' => $context['source_type'] ?? null,
            'amount' => round($amount, 2),
            'balance_after' => round($balanceAfter, 2),
            'category' => $category,
            'source_name' => $context['source_name'] ?? null,
            'reference' => $context['reference'] ?? null,
            'notes' => $context['notes'] ?? null,
            'occurred_at' => $context['occurred_at'] ?? now(),
        ]);
    }
}
