<?php

namespace App\Support;

use Illuminate\Http\Request;

class CashierMode
{
    public const SESSION_KEY = 'cashier_mode';

    public static function isActive(?Request $request = null): bool
    {
        $request = $request ?? request();

        if (! $request || ! $request->hasSession()) {
            return false;
        }

        return (bool) $request->session()->get(self::SESSION_KEY, false);
    }

    public static function enable(Request $request): void
    {
        $request->session()->put(self::SESSION_KEY, true);
    }

    public static function disable(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }
}
