<?php

namespace App\Services;

use App\Enums\AffiliateStatus;
use App\Enums\ShareholderStatus;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthLoginService
{
    public function resolveLoginField(string $login): string
    {
        return filter_var(trim($login), FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
    }

    public function normalizeLogin(string $login, string $field): string
    {
        $login = trim($login);

        return $field === 'email' ? strtolower($login) : strtolower($login);
    }

    public function findUser(string $login, ?Business $business = null): ?User
    {
        $field = $this->resolveLoginField($login);
        $value = $this->normalizeLogin($login, $field);

        $query = User::query()->where($field, $value);

        if ($business) {
            $query->where('business_id', $business->id);
        } else {
            $query->where('is_super_admin', false)
                ->where('is_sub_admin', false)
                ->where(function ($q) {
                    $q->whereNotNull('business_id')
                        ->orWhere('is_affiliate', true)
                        ->orWhere('is_shareholder', true);
                });
        }

        return $query->first();
    }

    public function attemptAffiliate(string $login, string $password, bool $remember = false): ?User
    {
        $field = $this->resolveLoginField($login);
        $value = $this->normalizeLogin($login, $field);

        $user = User::query()
            ->where($field, $value)
            ->where('is_super_admin', false)
            ->where('is_sub_admin', false)
            ->where(function ($q) {
                $q->where(function ($dedicated) {
                    $dedicated->where('is_affiliate', true)->whereNull('business_id');
                })->orWhereHas('affiliateProfile', function ($profile) {
                    $profile->where('is_active', true)
                        ->where('status', AffiliateStatus::APPROVED);
                });
            })
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        Auth::login($user, $remember);

        return $user;
    }

    public function attemptShareholder(string $login, string $password, bool $remember = false): ?User
    {
        $field = $this->resolveLoginField($login);
        $value = $this->normalizeLogin($login, $field);

        $user = User::query()
            ->where($field, $value)
            ->where('is_super_admin', false)
            ->where('is_sub_admin', false)
            ->where(function ($q) {
                $q->where(function ($dedicated) {
                    $dedicated->where('is_shareholder', true)->whereNull('business_id');
                })->orWhereHas('shareholderProfile', function ($profile) {
                    $profile->where('is_active', true)
                        ->whereIn('status', ShareholderStatus::allocated());
                });
            })
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        Auth::login($user, $remember);

        return $user;
    }

    public function attempt(string $login, string $password, bool $remember = false, ?Business $business = null): ?User
    {
        $user = $this->findUser($login, $business);

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        Auth::login($user, $remember);

        return $user;
    }

    public function findPlatformAdmin(string $login): ?User
    {
        $login = trim($login);
        if ($login === '') {
            return null;
        }

        $normalized = strtolower($login);
        $platformQuery = User::query()->where(function ($q) {
            $q->where('is_super_admin', true)->orWhere('is_sub_admin', true);
        });

        $user = (clone $platformQuery)->where('username', $normalized)->first();
        if ($user) {
            return $user;
        }

        return (clone $platformQuery)->where('email', $normalized)->first();
    }

    public function attemptPlatformAdmin(string $login, string $password, bool $remember = false): ?User
    {
        $user = $this->findPlatformAdmin($login);

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        Auth::login($user, $remember);

        return $user;
    }
}
