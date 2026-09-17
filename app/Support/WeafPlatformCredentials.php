<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Crypt;

class WeafPlatformCredentials
{
    public static function autoProvisionEnabled(): bool
    {
        if (! EfrisCompliance::globallyEnabled()) {
            return false;
        }

        return (bool) (int) SystemSetting::get('efris_auto_provision_enabled', '1');
    }

    public static function usePlatformAccount(): bool
    {
        $credentials = static::credentials();

        return $credentials !== null;
    }

    public static function credentials(): ?array
    {
        $email = trim((string) SystemSetting::get('efris_platform_email', ''));

        if ($email === '') {
            return null;
        }

        $password = static::decryptPassword(
            (string) SystemSetting::get('efris_platform_password', '')
        );

        if ($password === null || $password === '') {
            return null;
        }

        return [
            'email' => $email,
            'password' => $password,
        ];
    }

    public static function storePassword(string $password): void
    {
        SystemSetting::set('efris_platform_password', Crypt::encryptString($password));
    }

    public static function hasStoredPassword(): bool
    {
        return filled(SystemSetting::get('efris_platform_password', ''));
    }

    protected static function decryptPassword(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return $value;
        }
    }
}
