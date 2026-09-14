<?php

use App\Services\AffiliateCommissionUpgradeService;
use Illuminate\Database\Migrations\Migration;

class UpgradeAffiliateFirstCommissionRates extends Migration
{
    public function up()
    {
        app(AffiliateCommissionUpgradeService::class)->upgradeLegacyFirstCommissions();
    }

    public function down()
    {
        // One-way data correction — prior flat-rate first commissions are not restored.
    }
}
