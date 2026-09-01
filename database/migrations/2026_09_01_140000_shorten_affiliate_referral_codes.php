<?php

use App\Models\Affiliate;
use App\Services\AffiliateReferralCodeGenerator;
use Illuminate\Database\Migrations\Migration;

class ShortenAffiliateReferralCodes extends Migration
{
    public function up()
    {
        $generator = app(AffiliateReferralCodeGenerator::class);

        Affiliate::withTrashed()->orderBy('id')->each(function (Affiliate $affiliate) use ($generator) {
            $code = strtolower(trim((string) $affiliate->code));

            if ($code !== '' && strlen($code) <= 8 && ! str_contains($code, '-')) {
                return;
            }

            $affiliate->update(['code' => $generator->generateUnique()]);
        });
    }

    public function down()
    {
        // Irreversible — old referral URLs would not be restored.
    }
}
