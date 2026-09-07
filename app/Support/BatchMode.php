<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Business;
use App\Models\SystemSetting;

class BatchMode
{
    public static function platformEnabled(): bool
    {
        return (bool) (int) SystemSetting::get('batch_mode_enabled', 0);
    }

    public static function businessEnabled(Business $business): bool
    {
        if (! self::platformEnabled()) {
            return false;
        }

        $settings = $business->settings ?? [];

        return (bool) ($settings['batch_mode'] ?? false);
    }

    public static function branchEnabled(Business $business, ?int $branchId): bool
    {
        if (! self::businessEnabled($business)) {
            return false;
        }

        if (! $branchId) {
            return true;
        }

        $branch = Branch::query()
            ->where('business_id', $business->id)
            ->whereKey($branchId)
            ->first();

        if (! $branch) {
            return true;
        }

        $branchSettings = $branch->settings ?? [];

        if (array_key_exists('batch_mode', $branchSettings)) {
            return (bool) $branchSettings['batch_mode'];
        }

        return true;
    }

    public static function active(Business $business, ?int $branchId = null): bool
    {
        return self::branchEnabled($business, $branchId);
    }
}
