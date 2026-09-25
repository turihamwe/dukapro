<?php

namespace App\Support;

use App\Models\Business;
use App\Models\SystemSetting;

class BusinessModeCompliance
{
    public const MODE_SERVICE = 'service';

    public const MODE_RENTAL = 'rental';

    public const MODE_INVENTORY_ONLY = 'inventory_only';

    public static function globallyEnabled(string $mode): bool
    {
        $config = config('business_modes.modes.' . $mode);

        if (! $config) {
            return false;
        }

        $stored = SystemSetting::get($config['system_setting']);

        if ($stored !== null && $stored !== '') {
            return (bool) (int) $stored;
        }

        return false;
    }

    public static function isAdminUnlocked(?Business $business, string $mode): bool
    {
        if (! static::globallyEnabled($mode) || ! $business) {
            return false;
        }

        $config = config('business_modes.modes.' . $mode);
        $settings = $business->settings ?? [];
        $key = $config['unlock_setting'] ?? null;

        return $key && ! empty($settings[$key]);
    }

    public static function isOwnerEnabled(?Business $business, string $mode): bool
    {
        if (! static::isAdminUnlocked($business, $mode) || ! $business) {
            return false;
        }

        $config = config('business_modes.modes.' . $mode);
        $settings = $business->settings ?? [];
        $key = $config['owner_setting'] ?? null;

        return $key && ! empty($settings[$key]);
    }

    public static function isActive(?Business $business, string $mode): bool
    {
        return static::isOwnerEnabled($business, $mode);
    }

    public static function serviceCatalogActive(?Business $business): bool
    {
        return static::isActive($business, self::MODE_SERVICE);
    }

    public static function rentalModeActive(?Business $business): bool
    {
        return static::isActive($business, self::MODE_RENTAL);
    }

    public static function inventoryOnlyModeActive(?Business $business): bool
    {
        return static::isActive($business, self::MODE_INVENTORY_ONLY);
    }

    /**
     * @return array<string, mixed>
     */
    public static function registrationPreset(string $operatingMode): array
    {
        $settings = [];

        if ($operatingMode === \App\Enums\BusinessOperatingMode::SERVICE_BASED) {
            $settings['service_mode_admin_unlocked'] = true;
            $settings['service_based_mode_enabled'] = true;
        }

        if ($operatingMode === \App\Enums\BusinessOperatingMode::RENTAL) {
            $settings['rental_mode_admin_unlocked'] = true;
            $settings['rental_mode_enabled'] = true;
        }

        if ($operatingMode === \App\Enums\BusinessOperatingMode::INVENTORY_ONLY) {
            $settings['inventory_only_mode_admin_unlocked'] = true;
            $settings['inventory_only_mode_enabled'] = true;
        }

        return $settings;
    }

    public static function setAdminUnlock(Business $business, string $mode, bool $unlocked): void
    {
        $config = config('business_modes.modes.' . $mode);

        if (! $config) {
            return;
        }

        $settings = $business->settings ?? [];
        $settings[$config['unlock_setting']] = $unlocked;

        if (! $unlocked && ! empty($config['owner_setting'])) {
            $settings[$config['owner_setting']] = false;
        }

        $business->settings = $settings;
        $business->save();
    }

    public static function setOwnerEnabled(Business $business, string $mode, bool $enabled): void
    {
        if (! static::isAdminUnlocked($business, $mode)) {
            return;
        }

        $config = config('business_modes.modes.' . $mode);

        if (! $config || empty($config['owner_setting'])) {
            return;
        }

        $settings = $business->settings ?? [];
        $settings[$config['owner_setting']] = $enabled;
        $business->settings = $settings;
        $business->save();
    }
}
