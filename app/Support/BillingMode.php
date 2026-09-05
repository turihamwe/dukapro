<?php

namespace App\Support;

use App\Models\SystemSetting;

class BillingMode
{
    public const UNIFIED = 'unified';

    public const ADDONS = 'addons';

    public static function current(): string
    {
        $mode = (string) SystemSetting::get('billing_mode', config('billing.default_mode', self::UNIFIED));

        return in_array($mode, [self::UNIFIED, self::ADDONS], true) ? $mode : self::UNIFIED;
    }

    public static function isUnified(): bool
    {
        return self::current() === self::UNIFIED;
    }

    public static function isAddons(): bool
    {
        return self::current() === self::ADDONS;
    }
}
