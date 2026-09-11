<?php

namespace App\Support;

use App\Enums\AffiliateStatus;
use App\Models\Affiliate;

class AffiliateStatusPresenter
{
    public static function for(Affiliate $affiliate): array
    {
        $status = (string) $affiliate->status;
        $isActive = (bool) $affiliate->is_active;

        if ($status === AffiliateStatus::APPROVED && $isActive) {
            return [
                'label' => 'Active',
                'classes' => 'bg-emerald-100 text-emerald-800',
            ];
        }

        if ($status === AffiliateStatus::PENDING) {
            return [
                'label' => 'Pending',
                'classes' => 'bg-amber-100 text-amber-800',
            ];
        }

        if ($status === AffiliateStatus::REJECTED) {
            return [
                'label' => 'Rejected',
                'classes' => 'bg-rose-100 text-rose-800',
            ];
        }

        return [
            'label' => 'Disabled',
            'classes' => 'bg-rose-100 text-rose-800',
        ];
    }
}
