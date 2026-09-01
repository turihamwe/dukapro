<?php

namespace App\Services;

use App\Enums\AffiliateStatus;
use App\Enums\UserRole;
use App\Models\Affiliate;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AffiliateRegistrationService
{
    public function isRecruitmentOpen(): bool
    {
        if (! config('affiliates.recruitment_open', true)) {
            return false;
        }

        $setting = SystemSetting::get('affiliate_recruitment_open');

        if ($setting !== null && $setting !== '') {
            return filter_var($setting, FILTER_VALIDATE_BOOLEAN);
        }

        return true;
    }

    public function apply(array $data): Affiliate
    {
        return DB::transaction(function () use ($data) {
            $username = $this->uniqueUsername($data['email'], $data['name']);
            $code = $this->uniqueCode($data['name']);

            $affiliate = Affiliate::create([
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'phone' => $data['phone'] ?? null,
                'code' => $code,
                'commission_rate' => config('affiliates.default_commission_rate', 0.10),
                'status' => AffiliateStatus::PENDING,
                'is_active' => false,
                'application_message' => $data['application_message'] ?? null,
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

            return $affiliate->fresh(['user']);
        });
    }

    public function approve(Affiliate $affiliate, User $approver): Affiliate
    {
        $affiliate->update([
            'status' => AffiliateStatus::APPROVED,
            'is_active' => true,
            'approved_at' => now(),
            'approved_by' => $approver->id,
        ]);

        if ($affiliate->user) {
            $affiliate->user->update(['is_active' => true]);
        }

        return $affiliate->fresh();
    }

    public function reject(Affiliate $affiliate, User $approver): Affiliate
    {
        $affiliate->update([
            'status' => AffiliateStatus::REJECTED,
            'is_active' => false,
            'approved_by' => $approver->id,
        ]);

        if ($affiliate->user) {
            $affiliate->user->update(['is_active' => false]);
        }

        return $affiliate->fresh();
    }

    public function setActive(Affiliate $affiliate, bool $active): Affiliate
    {
        if ($affiliate->status !== AffiliateStatus::APPROVED && $active) {
            $affiliate->status = AffiliateStatus::APPROVED;
            $affiliate->approved_at = $affiliate->approved_at ?? now();
        }

        $affiliate->is_active = $active;

        if (! $active && $affiliate->status === AffiliateStatus::APPROVED) {
            $affiliate->status = AffiliateStatus::SUSPENDED;
        } elseif ($active) {
            $affiliate->status = AffiliateStatus::APPROVED;
        }

        $affiliate->save();

        if ($affiliate->user) {
            $affiliate->user->update(['is_active' => $active]);
        }

        return $affiliate->fresh();
    }

    protected function uniqueCode(string $name): string
    {
        $base = Str::slug($name) ?: 'partner';
        $base = Str::limit($base, 20, '');
        $code = $base . '-' . Str::lower(Str::random(6));
        $counter = 1;

        while (Affiliate::where('code', $code)->exists()) {
            $code = $base . '-' . Str::lower(Str::random(6)) . $counter;
            $counter++;
        }

        return $code;
    }

    protected function uniqueUsername(string $email, string $name): string
    {
        $base = Str::slug(Str::before($email, '@')) ?: Str::slug($name) ?: 'affiliate';
        $username = Str::limit($base, 40, '');
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $username = Str::limit($base, 35, '') . $counter;
            $counter++;
        }

        return strtolower($username);
    }
}
