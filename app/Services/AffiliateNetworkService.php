<?php

namespace App\Services;

use App\Enums\AffiliateStatus;
use App\Enums\UserRole;
use App\Models\Affiliate;
use App\Models\AffiliateTeamPayout;
use App\Models\AffiliateWithdrawalRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AffiliateNetworkService
{
    protected AffiliateReferralCodeGenerator $codeGenerator;

    public function __construct(AffiliateReferralCodeGenerator $codeGenerator)
    {
        $this->codeGenerator = $codeGenerator;
    }

    public function createSubAffiliate(Affiliate $parent, array $data, ?User $creator = null): Affiliate
    {
        abort_unless($parent->isApproved(), 422, 'Parent affiliate must be approved.');

        return DB::transaction(function () use ($parent, $data, $creator) {
            $username = strtolower(trim($data['username']));
            $code = $this->codeGenerator->generateUnique();

            $affiliate = Affiliate::create([
                'parent_affiliate_id' => $parent->id,
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'phone' => $data['phone'] ?? null,
                'code' => $code,
                'commission_rate' => $parent->commission_rate,
                'wallet_balance' => 0,
                'status' => AffiliateStatus::APPROVED,
                'is_active' => true,
                'approved_at' => now(),
                'approved_by' => $creator ? $creator->id : null,
            ]);

            $user = User::create([
                'business_id' => null,
                'name' => $data['name'],
                'username' => $username,
                'email' => strtolower($data['email']),
                'password' => Hash::make($data['password']),
                'role' => UserRole::AFFILIATE,
                'is_active' => true,
                'is_affiliate' => true,
                'ui_theme' => 'modern',
            ]);

            $affiliate->update(['user_id' => $user->id]);

            return $affiliate->fresh(['user', 'parent']);
        });
    }

    public function payoutToSubAffiliate(Affiliate $parent, Affiliate $sub, float $amount, User $actor, ?string $notes = null): AffiliateTeamPayout
    {
        if ((int) $sub->parent_affiliate_id !== (int) $parent->id) {
            throw ValidationException::withMessages([
                'sub_affiliate_id' => 'Selected affiliate is not on your team.',
            ]);
        }

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Enter a valid payout amount.',
            ]);
        }

        return DB::transaction(function () use ($parent, $sub, $amount, $actor, $notes) {
            $parent = Affiliate::query()->whereKey($parent->id)->lockForUpdate()->firstOrFail();
            $sub = Affiliate::query()->whereKey($sub->id)->lockForUpdate()->firstOrFail();

            if ((float) $parent->wallet_balance < $amount) {
                throw ValidationException::withMessages([
                    'amount' => 'Insufficient wallet balance for this payout.',
                ]);
            }

            $parent->wallet_balance = (float) $parent->wallet_balance - $amount;
            $sub->wallet_balance = (float) $sub->wallet_balance + $amount;
            $parent->save();
            $sub->save();

            return AffiliateTeamPayout::create([
                'parent_affiliate_id' => $parent->id,
                'sub_affiliate_id' => $sub->id,
                'amount' => $amount,
                'notes' => $notes,
                'created_by' => $actor->id,
            ]);
        });
    }

    public function requestWithdrawal(Affiliate $affiliate, array $data): AffiliateWithdrawalRequest
    {
        $amount = (float) ($data['amount'] ?? 0);

        if ($amount <= 0 || $amount > (float) $affiliate->wallet_balance) {
            throw ValidationException::withMessages([
                'amount' => 'Amount exceeds your available wallet balance.',
            ]);
        }

        return DB::transaction(function () use ($affiliate, $data, $amount) {
            $affiliate = Affiliate::query()->whereKey($affiliate->id)->lockForUpdate()->firstOrFail();

            if ($amount > (float) $affiliate->wallet_balance) {
                throw ValidationException::withMessages([
                    'amount' => 'Amount exceeds your available wallet balance.',
                ]);
            }

            $affiliate->wallet_balance = (float) $affiliate->wallet_balance - $amount;
            $affiliate->save();

            return AffiliateWithdrawalRequest::create([
                'affiliate_id' => $affiliate->id,
                'amount' => $amount,
                'status' => AffiliateWithdrawalRequest::STATUS_PENDING,
                'payout_method' => $data['payout_method'] ?? null,
                'payout_account' => $data['payout_account'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    public function processWithdrawal(AffiliateWithdrawalRequest $request, User $processor, string $status): AffiliateWithdrawalRequest
    {
        if (! in_array($status, [AffiliateWithdrawalRequest::STATUS_PAID, AffiliateWithdrawalRequest::STATUS_REJECTED], true)) {
            throw ValidationException::withMessages(['status' => 'Invalid withdrawal status.']);
        }

        return DB::transaction(function () use ($request, $processor, $status) {
            $request = AffiliateWithdrawalRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if ($request->status !== AffiliateWithdrawalRequest::STATUS_PENDING) {
                throw ValidationException::withMessages(['status' => 'This withdrawal request was already processed.']);
            }

            if ($status === AffiliateWithdrawalRequest::STATUS_REJECTED) {
                $affiliate = Affiliate::query()->whereKey($request->affiliate_id)->lockForUpdate()->firstOrFail();
                $affiliate->wallet_balance = (float) $affiliate->wallet_balance + (float) $request->amount;
                $affiliate->save();
            }

            $request->update([
                'status' => $status,
                'processed_at' => now(),
                'processed_by' => $processor->id,
            ]);

            return $request->fresh();
        });
    }

    public function creditWallet(Affiliate $affiliate, float $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        Affiliate::query()->whereKey($affiliate->id)->increment('wallet_balance', $amount);
    }
}
