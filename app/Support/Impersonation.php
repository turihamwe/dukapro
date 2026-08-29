<?php

namespace App\Support;

use Illuminate\Http\Request;

class Impersonation
{
    public const SESSION_KEY = 'impersonator_id';

    public static function isActive(?Request $request = null): bool
    {
        $request = $request ?? request();

        return $request && $request->hasSession() && $request->session()->has(self::SESSION_KEY);
    }

    public static function impersonatorId(?Request $request = null): ?int
    {
        $request = $request ?? request();

        if (! self::isActive($request)) {
            return null;
        }

        return (int) $request->session()->get(self::SESSION_KEY);
    }

    public static function start(Request $request, int $superAdminId): void
    {
        $request->session()->put(self::SESSION_KEY, $superAdminId);
    }

    public static function stop(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }
}
