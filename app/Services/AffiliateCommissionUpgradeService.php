<?php

namespace App\Services;

use App\Models\AffiliateCommission;
use App\Support\AffiliateCommissionRates;
use Illuminate\Support\Facades\DB;

class AffiliateCommissionUpgradeService
{
    protected AffiliateNetworkService $affiliateNetworkService;

    public function __construct(AffiliateNetworkService $affiliateNetworkService)
    {
        $this->affiliateNetworkService = $affiliateNetworkService;
    }

    /**
     * Upgrade each affiliate's first commission per referred business to the first-payment rate.
     *
     * @return array{upgraded:int,wallet_credited:float}
     */
    public function upgradeLegacyFirstCommissions(): array
    {
        $firstRate = AffiliateCommissionRates::firstRate();
        $upgraded = 0;
        $walletCredited = 0.0;

        $pairs = AffiliateCommission::query()
            ->select('affiliate_id', 'business_id')
            ->where('status', '!=', 'cancelled')
            ->groupBy('affiliate_id', 'business_id')
            ->get();

        foreach ($pairs as $pair) {
            $commission = AffiliateCommission::query()
                ->where('affiliate_id', $pair->affiliate_id)
                ->where('business_id', $pair->business_id)
                ->where('status', '!=', 'cancelled')
                ->orderBy('id')
                ->first();

            if (! $commission || (float) $commission->commission_rate >= $firstRate - 0.0001) {
                continue;
            }

            DB::transaction(function () use ($commission, $firstRate, &$upgraded, &$walletCredited) {
                $locked = AffiliateCommission::query()
                    ->whereKey($commission->id)
                    ->lockForUpdate()
                    ->first();

                if (! $locked || (float) $locked->commission_rate >= $firstRate - 0.0001) {
                    return;
                }

                $previousAmount = (float) $locked->commission_amount;
                $newAmount = round((float) $locked->payment_amount * $firstRate, 2);

                $locked->update([
                    'commission_rate' => $firstRate,
                    'commission_amount' => $newAmount,
                ]);

                $upgraded++;

                if ($locked->status === 'paid' && $newAmount > $previousAmount && $locked->affiliate) {
                    $difference = $newAmount - $previousAmount;
                    $this->affiliateNetworkService->creditWallet($locked->affiliate, $difference);
                    $walletCredited += $difference;
                }
            });
        }

        return [
            'upgraded' => $upgraded,
            'wallet_credited' => round($walletCredited, 2),
        ];
    }
}
