<?php

namespace App\Support;

class AffiliateTargets
{
    public static function tiers(): array
    {
        return config('affiliates.onboarding_targets', [
            1 => ['label' => 'Target 1', 'min' => 10],
            2 => ['label' => 'Target 2', 'min' => 50],
            3 => ['label' => 'Target 3', 'min' => 100],
        ]);
    }

    public static function statusForCount(int $onboardedCount): array
    {
        $hitTiers = [];
        $nextTarget = null;

        foreach (self::tiers() as $tier => $meta) {
            if ($onboardedCount >= (int) $meta['min']) {
                $hitTiers[] = (int) $tier;
            } elseif ($nextTarget === null) {
                $nextTarget = $meta;
            }
        }

        return [
            'hit_tiers' => $hitTiers,
            'highest_tier' => empty($hitTiers) ? null : max($hitTiers),
            'next_target' => $nextTarget,
        ];
    }

    public static function labelForCount(int $onboardedCount): string
    {
        $status = self::statusForCount($onboardedCount);

        if ($status['highest_tier']) {
            $tier = self::tiers()[$status['highest_tier']];

            return $tier['label'] . ' achieved';
        }

        if ($status['next_target']) {
            $remaining = max(0, (int) $status['next_target']['min'] - $onboardedCount);

            return $remaining . ' to ' . $status['next_target']['label'];
        }

        return 'No target yet';
    }
}
