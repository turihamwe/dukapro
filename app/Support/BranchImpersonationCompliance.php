<?php

namespace App\Support;

use App\Models\Business;
use App\Models\SystemSetting;

class BranchImpersonationCompliance
{
    /**
     * Level 1 — system-wide master switch.
     */
    public static function globallyEnabled(): bool
    {
        $stored = SystemSetting::get('use_branch_impersonation');

        if ($stored !== null && $stored !== '') {
            return (bool) (int) $stored;
        }

        return (bool) config('business_branches.use_branch_impersonation', false);
    }

    /**
     * Level 2 — superadmin unlocked branch context switching for a business.
     */
    public static function isAdminUnlocked(?Business $business): bool
    {
        if (! static::globallyEnabled() || ! $business) {
            return false;
        }

        $key = config('business_branches.unlock_setting', 'branch_impersonation_admin_unlocked');
        $settings = $business->settings ?? [];

        return ! empty($settings[$key]);
    }

    public static function isActive(?Business $business): bool
    {
        return static::isAdminUnlocked($business);
    }

    public static function setAdminUnlock(Business $business, bool $unlocked): void
    {
        $key = config('business_branches.unlock_setting', 'branch_impersonation_admin_unlocked');
        $settings = $business->settings ?? [];
        $settings[$key] = $unlocked;
        $business->settings = $settings;
        $business->save();

        if (! $unlocked) {
            BranchImpersonation::leave();
        }
    }
}
