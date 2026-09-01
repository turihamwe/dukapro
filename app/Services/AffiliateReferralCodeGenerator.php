<?php

namespace App\Services;

use App\Models\Affiliate;

class AffiliateReferralCodeGenerator
{
    public function generateUnique(): string
    {
        $length = (int) config('affiliates.referral_code_length', 6);
        $charset = config('affiliates.referral_code_charset', '23456789abcdefghjkmnpqrstuvwxyz');
        $maxLength = (int) config('affiliates.referral_code_max_length', 8);

        do {
            $code = $this->randomCode($charset, $length);

            if (! Affiliate::withTrashed()->where('code', $code)->exists()) {
                return $code;
            }

            $length++;
        } while ($length <= $maxLength);

        throw new \RuntimeException('Unable to generate a unique affiliate referral code.');
    }

    protected function randomCode(string $charset, int $length): string
    {
        $maxIndex = strlen($charset) - 1;
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= $charset[random_int(0, $maxIndex)];
        }

        return $code;
    }
}
