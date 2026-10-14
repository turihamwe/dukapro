<?php

namespace App\Support;

use App\Models\Business;
use App\Models\SystemSetting;

class WalletLedgerAdjustmentCompliance
{
    /**
     * Level 1 — system-wide master switch.
     */
    public static function globallyEnabled(): bool
    {
        $stored = SystemSetting::get('use_wallet_ledger_adjustments');

        if ($stored !== null && $stored !== '') {
            return (bool) (int) $stored;
        }

        return (bool) config('payment_wallets.use_wallet_ledger_adjustments', false);
    }

    /**
     * Level 2 — superadmin unlocked ledger corrections for a specific business.
     */
    public static function isAdminUnlocked(?Business $business): bool
    {
        if (! static::globallyEnabled() || ! $business) {
            return false;
        }

        $key = config('payment_wallets.unlock_setting', 'wallet_ledger_adjustments_admin_unlocked');
        $settings = $business->settings ?? [];

        return ! empty($settings[$key]);
    }

    /**
     * Tenant-facing feature (edit/delete in wallet history).
     */
    public static function isActive(?Business $business): bool
    {
        return static::isAdminUnlocked($business)
            && PaymentWalletMode::active($business);
    }

    public static function setAdminUnlock(Business $business, bool $unlocked): void
    {
        $key = config('payment_wallets.unlock_setting', 'wallet_ledger_adjustments_admin_unlocked');
        $settings = $business->settings ?? [];
        $settings[$key] = $unlocked;
        $business->settings = $settings;
        $business->save();
    }
}
