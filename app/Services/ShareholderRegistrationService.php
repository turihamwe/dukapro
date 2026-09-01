<?php

namespace App\Services;

use App\Enums\ShareholderStatus;
use App\Enums\UserRole;
use App\Models\Shareholder;
use App\Models\ShareholderEarning;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ShareholderRegistrationService
{
    protected ShareAllocationService $allocationService;

    public function __construct(ShareAllocationService $allocationService)
    {
        $this->allocationService = $allocationService;
    }

    public function isSubscriptionOpen(): bool
    {
        if (! config('shareholders.subscription_open', true)) {
            return false;
        }

        $setting = SystemSetting::get('shareholder_subscription_open');

        if ($setting !== null && $setting !== '') {
            return filter_var($setting, FILTER_VALIDATE_BOOLEAN);
        }

        return true;
    }

    public function apply(array $data): Shareholder
    {
        $shares = (float) $data['shares'];
        $this->allocationService->validateAllocation($shares);

        return DB::transaction(function () use ($data, $shares) {
            $username = $this->uniqueUsername($data['email'], $data['name']);
            $capital = $this->allocationService->capitalForShares($shares);

            $shareholder = Shareholder::create([
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'phone' => $data['phone'] ?? null,
                'national_id' => $data['national_id'] ?? null,
                'shares_owned' => $shares,
                'capital_invested' => $capital,
                'total_earnings' => 0,
                'status' => ShareholderStatus::PENDING,
                'is_active' => false,
                'registered_at' => now(),
                'application_message' => $data['application_message'] ?? null,
            ]);

            $user = User::create([
                'business_id' => null,
                'name' => $data['name'],
                'username' => $username,
                'email' => strtolower($data['email']),
                'password' => Hash::make($data['password']),
                'role' => UserRole::SHAREHOLDER,
                'is_active' => true,
                'is_shareholder' => true,
                'ui_theme' => 'modern',
            ]);

            $shareholder->update(['user_id' => $user->id]);

            return $shareholder->fresh(['user']);
        });
    }

    public function approve(Shareholder $shareholder, User $approver): Shareholder
    {
        if ($shareholder->status !== ShareholderStatus::PENDING) {
            throw ValidationException::withMessages([
                'status' => 'Only pending applications can be approved.',
            ]);
        }

        $this->allocationService->validateAllocation(
            (float) $shareholder->shares_owned,
            $shareholder->id,
            $this->activeShareholderCountExcluding($shareholder) === 0
        );

        $shareholder->update([
            'status' => ShareholderStatus::ACTIVE,
            'is_active' => true,
            'approved_at' => now(),
            'approved_by' => $approver->id,
            'registered_at' => $shareholder->registered_at ?? now(),
        ]);

        if ($shareholder->user) {
            $shareholder->user->update(['is_active' => true]);
        }

        return $shareholder->fresh();
    }

    public function reject(Shareholder $shareholder, User $approver): Shareholder
    {
        $shareholder->update([
            'status' => ShareholderStatus::REJECTED,
            'is_active' => false,
            'approved_by' => $approver->id,
        ]);

        if ($shareholder->user) {
            $shareholder->user->update(['is_active' => false]);
        }

        return $shareholder->fresh();
    }

    public function setActive(Shareholder $shareholder, bool $active): Shareholder
    {
        if ($active && ! $shareholder->isContractComplete()) {
            $this->allocationService->validateAllocation(
                (float) $shareholder->shares_owned,
                $shareholder->id,
                false
            );
        }

        if ($active && ! in_array($shareholder->status, [ShareholderStatus::ACTIVE, ShareholderStatus::APPROVED, ShareholderStatus::COMPLETED], true)) {
            $shareholder->status = ShareholderStatus::ACTIVE;
            $shareholder->approved_at = $shareholder->approved_at ?? now();
        }

        $shareholder->is_active = $active;

        if (! $active && in_array($shareholder->status, [ShareholderStatus::ACTIVE, ShareholderStatus::APPROVED], true)) {
            $shareholder->status = ShareholderStatus::SUSPENDED;
        } elseif ($active && ! $shareholder->isContractComplete()) {
            $shareholder->status = ShareholderStatus::ACTIVE;
        }

        $shareholder->save();

        if ($shareholder->user) {
            $shareholder->user->update(['is_active' => $active && ! $shareholder->isContractComplete()]);
        }

        return $shareholder->fresh();
    }

    public function syncCapital(Shareholder $shareholder, float $shares): Shareholder
    {
        $this->allocationService->validateAllocation($shares, $shareholder->id, false);

        $shareholder->update([
            'shares_owned' => $shares,
            'capital_invested' => $this->allocationService->capitalForShares($shares),
        ]);

        return $shareholder->fresh();
    }

    protected function activeShareholderCountExcluding(Shareholder $shareholder): int
    {
        if ($shareholder->countsTowardAllocation()) {
            return $this->allocationService->activeShareholderCount($shareholder->id);
        }

        return $this->allocationService->activeShareholderCount();
    }

    protected function uniqueUsername(string $email, string $name): string
    {
        $base = Str::slug(Str::before($email, '@')) ?: Str::slug($name) ?: 'shareholder';
        $username = Str::limit($base, 40, '');
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $username = Str::limit($base, 35, '') . $counter;
            $counter++;
        }

        return strtolower($username);
    }
}
