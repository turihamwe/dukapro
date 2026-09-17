<?php

namespace App\Support;

use App\Models\Business;
use App\Models\EfrisSetting;
use App\Models\SystemSetting;

class EfrisCompliance
{
    /**
     * Level 1 — system-wide master switch ("Use EFRIS").
     */
    public static function globallyEnabled(): bool
    {
        $stored = SystemSetting::get('use_efris');

        if ($stored !== null && $stored !== '') {
            return (bool) (int) $stored;
        }

        return (bool) config('efris.use_efris', false);
    }

    /**
     * Level 2 — superadmin unlocked EFRIS for a specific business.
     */
    public static function isAdminUnlocked(?EfrisSetting $settings): bool
    {
        return static::globallyEnabled()
            && $settings
            && $settings->isUnlockedByAdmin();
    }

    /**
     * Level 3 — business owner turned EFRIS on in their settings.
     */
    public static function isOwnerEnabled(?Business $business): bool
    {
        if (! $business) {
            return false;
        }

        $settings = $business->relationLoaded('efrisSetting')
            ? $business->efrisSetting
            : $business->efrisSetting()->first();

        return static::isAdminUnlocked($settings)
            && $settings
            && $settings->efris_enabled;
    }

    /**
     * All three levels plus token/TIN configuration — required before URA transmission.
     */
    public static function isTransmissionAllowed(?Business $business): bool
    {
        if (! static::isOwnerEnabled($business)) {
            return false;
        }

        $settings = $business->efrisSetting;

        return $settings && filled($settings->resolveTin($business)) && $settings->hasStoredToken();
    }
}
