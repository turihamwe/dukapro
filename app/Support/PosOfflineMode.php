<?php

namespace App\Support;

use App\Models\SystemSetting;

class PosOfflineMode
{
    public static function enabled(): bool
    {
        return (bool) (int) SystemSetting::get('pos_offline_enabled', 1);
    }
}
