<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class EfrisSetting extends Model
{
    protected $fillable = [
        'business_id',
        'efris_enabled',
        'efris_admin_unlocked',
        'efris_tin',
        'efris_api_token',
        'weaf_email',
        'weaf_password',
        'weaf_token_expires_at',
        'provisioning_status',
        'provisioning_error',
        'efris_environment',
        'efris_branch_id',
        'default_buyer_tin',
    ];

    protected $casts = [
        'efris_enabled' => 'boolean',
        'efris_admin_unlocked' => 'boolean',
        'weaf_token_expires_at' => 'datetime',
    ];

    protected $hidden = [
        'efris_api_token',
        'weaf_password',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function resolveTin(?Business $business = null): ?string
    {
        $business = $business ?: $this->business;

        $tin = preg_replace('/\D+/', '', (string) ($this->efris_tin ?: optional($business)->tax_number));

        return $tin !== '' ? $tin : null;
    }

    public function isUnlockedByAdmin(): bool
    {
        return (bool) $this->efris_admin_unlocked;
    }

    public function isActive(): bool
    {
        return $this->isUnlockedByAdmin()
            && $this->efris_enabled;
    }

    public function isConfigured(): bool
    {
        return $this->isActive()
            && filled($this->resolveTin())
            && filled($this->getRawToken());
    }

    public function isProvisioned(): bool
    {
        return in_array($this->provisioning_status, ['connected', 'company_pending', 'token_ready'], true)
            && $this->hasStoredToken();
    }

    public function environmentHeader(): string
    {
        $environment = strtolower((string) $this->efris_environment);

        return config('efris.environments.' . $environment, 'Sandbox');
    }

    public function setEfrisApiTokenAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $this->attributes['efris_api_token'] = Crypt::encryptString($value);
    }

    public function setWeafPasswordAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $this->attributes['weaf_password'] = Crypt::encryptString($value);
    }

    public function getDecryptedWeafPassword(): ?string
    {
        $raw = $this->attributes['weaf_password'] ?? null;

        if ($raw === null || $raw === '') {
            return null;
        }

        try {
            return Crypt::decryptString($raw);
        } catch (\Throwable $e) {
            return $raw;
        }
    }

    public function getDecryptedApiToken(): ?string
    {
        $raw = $this->getRawToken();

        if ($raw === null || $raw === '') {
            return null;
        }

        try {
            return Crypt::decryptString($raw);
        } catch (\Throwable $e) {
            return $raw;
        }
    }

    public function getRawToken(): ?string
    {
        return $this->attributes['efris_api_token'] ?? null;
    }

    public function hasStoredToken(): bool
    {
        return filled($this->getRawToken());
    }
}
