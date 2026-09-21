<?php

namespace App\Support;

class BusinessEngagementTier
{
    public const ACTIVE = 'active';

    public const AT_RISK = 'at_risk';

    public const DORMANT = 'dormant';

    public static function label(string $tier): string
    {
        $labels = [
            self::ACTIVE => 'Active',
            self::AT_RISK => 'At risk',
            self::DORMANT => 'Dormant',
        ];

        return $labels[$tier] ?? ucfirst(str_replace('_', ' ', $tier));
    }

    public static function all(): array
    {
        return [self::ACTIVE, self::AT_RISK, self::DORMANT];
    }
}
