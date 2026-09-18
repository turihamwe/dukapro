<?php

namespace App\Services;

use App\Enums\AffiliateStatus;
use App\Enums\ShareholderStatus;
use App\Mail\ResetPasswordMail;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class PasswordResetService
{
    public const PORTAL_DEFAULT = 'default';

    public const PORTAL_SUPERADMIN = 'superadmin';

    public const PORTAL_BUSINESS = 'business';

    public const PORTAL_AFFILIATE = 'affiliate';

    public const PORTAL_SHAREHOLDER = 'shareholder';

    public function normalizePortal(?string $portal): string
    {
        $portal = strtolower(trim((string) $portal));

        $allowed = [
            self::PORTAL_DEFAULT,
            self::PORTAL_SUPERADMIN,
            self::PORTAL_BUSINESS,
            self::PORTAL_AFFILIATE,
            self::PORTAL_SHAREHOLDER,
        ];

        return in_array($portal, $allowed, true) ? $portal : self::PORTAL_DEFAULT;
    }

    public function findBusinessByPortalSlug(?string $portalSlug): ?Business
    {
        $portalSlug = trim((string) $portalSlug);

        if ($portalSlug === '') {
            return null;
        }

        return Business::query()->where('portal_slug', $portalSlug)->first();
    }

    public function findUserByEmailForPortal(string $email, string $portal, ?Business $business = null): ?User
    {
        $email = strtolower(trim($email));

        if ($email === '') {
            return null;
        }

        if ($portal === self::PORTAL_SUPERADMIN) {
            return User::query()
                ->where('email', $email)
                ->where(function ($query) {
                    $query->where('is_super_admin', true)
                        ->orWhere('is_sub_admin', true);
                })
                ->first();
        }

        if ($portal === self::PORTAL_BUSINESS) {
            if (! $business) {
                return null;
            }

            return User::query()
                ->where('email', $email)
                ->where('business_id', $business->id)
                ->first();
        }

        if ($portal === self::PORTAL_AFFILIATE) {
            return User::query()
                ->where('email', $email)
                ->where('is_super_admin', false)
                ->where('is_sub_admin', false)
                ->where(function ($query) {
                    $query->where(function ($dedicated) {
                        $dedicated->where('is_affiliate', true)->whereNull('business_id');
                    })->orWhereHas('affiliateProfile', function ($profile) {
                        $profile->where('is_active', true)
                            ->where('status', AffiliateStatus::APPROVED);
                    });
                })
                ->first();
        }

        if ($portal === self::PORTAL_SHAREHOLDER) {
            return User::query()
                ->where('email', $email)
                ->where('is_super_admin', false)
                ->where('is_sub_admin', false)
                ->where(function ($query) {
                    $query->where(function ($dedicated) {
                        $dedicated->where('is_shareholder', true)->whereNull('business_id');
                    })->orWhereHas('shareholderProfile', function ($profile) {
                        $profile->where('is_active', true)
                            ->whereIn('status', ShareholderStatus::allocated());
                    });
                })
                ->first();
        }

        return User::query()
            ->where('email', $email)
            ->where('is_super_admin', false)
            ->where('is_sub_admin', false)
            ->where(function ($query) {
                $query->whereNotNull('business_id')
                    ->orWhere('is_affiliate', true)
                    ->orWhere('is_shareholder', true);
            })
            ->first();
    }

    public function sendResetLink(string $email, string $portal, ?Business $business = null): string
    {
        $user = $this->findUserByEmailForPortal($email, $portal, $business);

        if ($user && $user->email) {
            $token = Password::broker()->createToken($user);
            $resetUrl = $this->resetUrl($token, $user->email, $portal, $business);

            Mail::to($user->email)->send(new ResetPasswordMail($user, $resetUrl));
        }

        return Password::RESET_LINK_SENT;
    }

    public function resetPassword(array $credentials, string $portal, ?Business $business = null): string
    {
        $user = $this->findUserByEmailForPortal($credentials['email'] ?? '', $portal, $business);

        if (! $user) {
            return Password::INVALID_USER;
        }

        return Password::broker()->reset(
            [
                'email' => $user->email,
                'password' => $credentials['password'],
                'password_confirmation' => $credentials['password_confirmation'] ?? '',
                'token' => $credentials['token'],
            ],
            function (User $account, string $password) {
                $account->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );
    }

    public function loginUrl(string $portal, ?Business $business = null): string
    {
        if ($portal === self::PORTAL_SUPERADMIN) {
            return route('superadmin.login');
        }

        if ($portal === self::PORTAL_BUSINESS && $business) {
            return route('business.login', ['portal' => $business->portal_slug]);
        }

        if ($portal === self::PORTAL_AFFILIATE) {
            return route('affiliate.login');
        }

        if ($portal === self::PORTAL_SHAREHOLDER) {
            return route('shareholder.login');
        }

        return route('login');
    }

    public function forgotPasswordUrl(string $portal, ?Business $business = null): string
    {
        $params = ['portal' => $portal];

        if ($portal === self::PORTAL_BUSINESS && $business) {
            $params['business'] = $business->portal_slug;
        }

        return route('password.request', $params);
    }

    public function portalLabel(string $portal, ?Business $business = null): string
    {
        if ($portal === self::PORTAL_SUPERADMIN) {
            return 'Platform admin';
        }

        if ($portal === self::PORTAL_BUSINESS && $business) {
            return $business->name;
        }

        if ($portal === self::PORTAL_AFFILIATE) {
            return 'Affiliate';
        }

        if ($portal === self::PORTAL_SHAREHOLDER) {
            return 'Shareholder';
        }

        return platform_brand('name');
    }

    protected function resetUrl(string $token, string $email, string $portal, ?Business $business = null): string
    {
        $params = [
            'token' => $token,
            'email' => $email,
            'portal' => $portal,
        ];

        if ($portal === self::PORTAL_BUSINESS && $business) {
            $params['business'] = $business->portal_slug;
        }

        return url(route('password.reset', $params, false));
    }
}
