<?php

namespace App\Models;

use App\Enums\AffiliateStatus;
use App\Enums\ShareholderStatus;
use App\Enums\UserRole;
use App\Support\CashierMode;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'business_id',
        'name',
        'username',
        'email',
        'phone',
        'password',
        'role',
        'branch_name',
        'ui_theme',
        'is_active',
        'is_super_admin',
        'is_sub_admin',
        'is_affiliate',
        'is_shareholder',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'is_super_admin' => 'boolean',
        'is_sub_admin' => 'boolean',
        'is_affiliate' => 'boolean',
        'is_shareholder' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function affiliateProfile(): HasOne
    {
        return $this->hasOne(Affiliate::class);
    }

    public function shareholderProfile(): HasOne
    {
        return $this->hasOne(Shareholder::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function isOwner(): bool
    {
        return $this->role === UserRole::OWNER;
    }

    public function isManager(): bool
    {
        return $this->role === UserRole::MANAGER;
    }

    public function isCashier(): bool
    {
        return $this->role === UserRole::CASHIER;
    }

    public function isSupervisor(): bool
    {
        return $this->role === UserRole::SUPERVISOR;
    }

    public function canSwitchToCashierMode(): bool
    {
        return $this->isOwner() || $this->isManager() || $this->isSupervisor();
    }

    public function usesCashierExperience(): bool
    {
        if ($this->isCashier()) {
            return true;
        }

        return $this->canSwitchToCashierMode() && CashierMode::isActive();
    }

    public function isStaff(): bool
    {
        return in_array($this->role, UserRole::staffRoles(), true);
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    public function isSubAdmin(): bool
    {
        return (bool) $this->is_sub_admin;
    }

    public function isPlatformAdmin(): bool
    {
        return $this->isSuperAdmin() || $this->isSubAdmin();
    }

    public function isBusinessUser(): bool
    {
        return (bool) $this->business_id && ! $this->isPlatformAdmin();
    }

    public function isAffiliate(): bool
    {
        return (bool) $this->is_affiliate;
    }

    public function isDedicatedAffiliateAccount(): bool
    {
        return $this->is_affiliate && ! $this->business_id;
    }

    public function isDedicatedShareholderAccount(): bool
    {
        return $this->is_shareholder && ! $this->business_id;
    }

    public function hasAffiliatePortalAccess(): bool
    {
        if ($this->isPlatformAdmin()) {
            return false;
        }

        if ($this->isDedicatedAffiliateAccount()) {
            return true;
        }

        $profile = $this->relationLoaded('affiliateProfile')
            ? $this->affiliateProfile
            : $this->affiliateProfile()->first();

        return $profile
            && $profile->is_active
            && in_array($profile->status, [AffiliateStatus::APPROVED], true);
    }

    public function isShareholder(): bool
    {
        return (bool) $this->is_shareholder;
    }

    public function hasShareholderPortalAccess(): bool
    {
        if ($this->isPlatformAdmin()) {
            return false;
        }

        if ($this->isDedicatedShareholderAccount()) {
            return true;
        }

        $profile = $this->relationLoaded('shareholderProfile')
            ? $this->shareholderProfile
            : $this->shareholderProfile()->first();

        return $profile
            && $profile->is_active
            && in_array($profile->status, ShareholderStatus::allocated(), true);
    }
}
