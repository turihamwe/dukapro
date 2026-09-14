<?php

namespace App\Support;

use App\Models\Affiliate;
use App\Models\SystemSetting;

class AffiliateCommissionRates
{
    public static function firstRate(): float
    {
        return (float) SystemSetting::get(
            'affiliate_first_commission_rate',
            config('affiliates.first_commission_rate', 0.50)
        );
    }

    public static function subsequentRate(): float
    {
        return (float) SystemSetting::get(
            'affiliate_subsequent_commission_rate',
            config('affiliates.subsequent_commission_rate', 0.10)
        );
    }

    public static function forReferredBusiness(Affiliate $affiliate, int $businessId): float
    {
        $count = $affiliate->commissions()
            ->where('business_id', $businessId)
            ->where('status', '!=', 'cancelled')
            ->count();

        return $count === 0 ? self::firstRate() : self::subsequentRate();
    }
}
