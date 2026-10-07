<?php

namespace App\Services;

use App\Helpers\AuditLogger;
use App\Models\Business;
use App\Models\PaymentWallet;
use App\Models\User;
use App\Support\PaymentWalletMode;
use Illuminate\Support\Facades\DB;
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

    public function deposit(PaymentWallet $wallet, float $amount, User $user): PaymentWallet
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

        return DB::transaction(function () use ($wallet, $amount, $user) {
            $locked = PaymentWallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();
            $oldBalance = (float) $locked->current_balance;
            $locked->current_balance = round($oldBalance + $amount, 2);
            $locked->save();
            $fresh = $locked->fresh();

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

    public function withdraw(PaymentWallet $wallet, float $amount, User $user): PaymentWallet
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

        return DB::transaction(function () use ($wallet, $amount, $user) {
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
}
