<?php

namespace App\Services;

use App\Enums\AffiliateStatus;
use App\Models\Affiliate;

class SystemAffiliateService
{
    public function systemCode(): string
    {
        return strtolower((string) config('affiliates.system_default_code', 'admin'));
    }

    public function ensureExists(): Affiliate
    {
        $code = $this->systemCode();

        $affiliate = Affiliate::withTrashed()->where('code', $code)->first();

        if ($affiliate) {
            if ($affiliate->trashed()) {
                $affiliate->restore();
            }

            if ($affiliate->status !== AffiliateStatus::APPROVED || ! $affiliate->is_active) {
                $affiliate->update([
                    'status' => AffiliateStatus::APPROVED,
                    'is_active' => true,
                    'approved_at' => $affiliate->approved_at ?? now(),
                ]);
            }

            return $affiliate->fresh();
        }

        return Affiliate::create([
            'name' => config('affiliates.system_default_name', 'DukaPro Direct'),
            'email' => config('affiliates.system_default_email', 'admin@dukapro.com'),
            'code' => $code,
            'commission_rate' => 0,
            'status' => AffiliateStatus::APPROVED,
            'is_active' => true,
            'approved_at' => now(),
            'application_message' => 'System default affiliate for direct and paid traffic.',
        ]);
    }

    public function default(): Affiliate
    {
        return $this->ensureExists();
    }

    public function isSystemDefault(Affiliate $affiliate): bool
    {
        return strtolower((string) $affiliate->code) === $this->systemCode();
    }
}
