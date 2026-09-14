<?php

namespace App\Console\Commands;

use App\Services\AffiliateCommissionUpgradeService;
use Illuminate\Console\Command;

class UpgradeAffiliateFirstCommissions extends Command
{
    protected $signature = 'affiliates:upgrade-first-commissions';

    protected $description = 'Upgrade each affiliate\'s first commission per referred business to the first-payment rate';

    public function handle(AffiliateCommissionUpgradeService $service): int
    {
        $result = $service->upgradeLegacyFirstCommissions();

        $this->info('Upgraded ' . $result['upgraded'] . ' first-payment commission(s).');
        $this->info('Wallet top-up credited: UGX ' . number_format($result['wallet_credited'], 0));

        return self::SUCCESS;
    }
}
