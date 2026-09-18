<?php

namespace App\Services;

use App\Models\AffiliateReferral;
use App\Models\Business;

class AffiliateReferralService
{
    public function recordFromBusiness(Business $business): ?AffiliateReferral
    {
        if (! $business->sponsor_id) {
            return null;
        }

        return AffiliateReferral::query()->firstOrCreate(
            ['business_id' => $business->id],
            [
                'affiliate_id' => $business->sponsor_id,
                'referred_at' => $business->created_at ?? now(),
            ]
        );
    }
}
