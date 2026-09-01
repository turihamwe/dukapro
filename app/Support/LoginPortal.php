<?php

namespace App\Support;

use Illuminate\Http\Request;

class LoginPortal
{
    public const SESSION_KEY = 'login_portal';

    public const BUSINESS = 'business';

    public const AFFILIATE = 'affiliate';

    public const SHAREHOLDER = 'shareholder';

    public static function get(?Request $request = null): ?string
    {
        $request = $request ?? request();

        if (! $request || ! $request->hasSession()) {
            return null;
        }

        return $request->session()->get(self::SESSION_KEY);
    }

    public static function set(Request $request, string $portal): void
    {
        $request->session()->put(self::SESSION_KEY, $portal);
    }

    public static function clear(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }

    public static function isBusiness(?Request $request = null): bool
    {
        return self::get($request) === self::BUSINESS;
    }

    public static function isAffiliate(?Request $request = null): bool
    {
        return self::get($request) === self::AFFILIATE;
    }

    public static function isShareholder(?Request $request = null): bool
    {
        return self::get($request) === self::SHAREHOLDER;
    }
}
