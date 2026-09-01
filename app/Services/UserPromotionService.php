<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Affiliate;
use App\Models\Shareholder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserPromotionService
{
    protected AffiliateRegistrationService $affiliateRegistration;

    protected ShareholderRegistrationService $shareholderRegistration;

    protected ShareAllocationService $allocationService;

    public function __construct(
        AffiliateRegistrationService $affiliateRegistration,
        ShareholderRegistrationService $shareholderRegistration,
        ShareAllocationService $allocationService
    ) {
        $this->affiliateRegistration = $affiliateRegistration;
        $this->shareholderRegistration = $shareholderRegistration;
        $this->allocationService = $allocationService;
    }

    public function canPromoteToAffiliate(User $user): bool
    {
        if ($user->isPlatformAdmin() || $user->isDedicatedAffiliateAccount()) {
            return false;
        }

        if ($user->affiliateProfile) {
            return false;
        }

        return ! Affiliate::where('email', strtolower($user->email))->exists();
    }

    public function canPromoteToShareholder(User $user): bool
    {
        if ($user->isPlatformAdmin() || $user->isShareholder()) {
            return false;
        }

        if ($user->shareholderProfile) {
            return false;
        }

        return ! Shareholder::where('email', strtolower($user->email))->exists();
    }

    public function promoteToAffiliate(User $user, User $approver): Affiliate
    {
        if (! $this->canPromoteToAffiliate($user)) {
            throw ValidationException::withMessages([
                'user' => 'This user cannot be promoted to affiliate.',
            ]);
        }

        return DB::transaction(function () use ($user, $approver) {
            $affiliate = Affiliate::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => strtolower($user->email),
                'phone' => $user->phone,
                'code' => $this->uniqueAffiliateCode($user->name),
                'commission_rate' => config('affiliates.default_commission_rate', 0.10),
                'status' => \App\Enums\AffiliateStatus::PENDING,
                'is_active' => false,
            ]);

            // Keep the user's business role intact — separation lives on affiliates table.
            return $this->affiliateRegistration->approve($affiliate->fresh(), $approver);
        });
    }

    public function promoteToShareholder(User $user, User $approver, ?float $shares = null): Shareholder
    {
        if (! $this->canPromoteToShareholder($user)) {
            throw ValidationException::withMessages([
                'user' => 'This user cannot be promoted to shareholder.',
            ]);
        }

        $shares = $shares ?? (float) config('shareholders.default_promotion_shares', 1);
        $this->allocationService->validateAllocation($shares);

        return DB::transaction(function () use ($user, $approver, $shares) {
            $capital = $this->allocationService->capitalForShares($shares);

            $shareholder = Shareholder::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => strtolower($user->email),
                'phone' => $user->phone,
                'shares_owned' => $shares,
                'capital_invested' => $capital,
                'total_earnings' => 0,
                'status' => \App\Enums\ShareholderStatus::PENDING,
                'is_active' => false,
                'registered_at' => now(),
            ]);

            // Keep the user's business role intact — separation lives on shareholders table.
            return $this->shareholderRegistration->approve($shareholder->fresh(), $approver);
        });
    }

    protected function uniqueAffiliateCode(string $name): string
    {
        $base = \Illuminate\Support\Str::slug($name) ?: 'partner';
        $base = \Illuminate\Support\Str::limit($base, 20, '');
        $code = $base . '-' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(6));
        $counter = 1;

        while (Affiliate::where('code', $code)->exists()) {
            $code = $base . '-' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(6)) . $counter;
            $counter++;
        }

        return $code;
    }
}
