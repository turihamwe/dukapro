<?php

namespace App\Models;

use App\Enums\AffiliateStatus;
use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Affiliate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'parent_affiliate_id',
        'name',
        'email',
        'phone',
        'code',
        'commission_rate',
        'wallet_balance',
        'status',
        'is_active',
        'application_message',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'commission_rate' => 'float',
        'wallet_balance' => 'float',
        'is_active' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_affiliate_id');
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(self::class, 'parent_affiliate_id');
    }

    public function teamPayoutsSent(): HasMany
    {
        return $this->hasMany(AffiliateTeamPayout::class, 'parent_affiliate_id');
    }

    public function teamPayoutsReceived(): HasMany
    {
        return $this->hasMany(AffiliateTeamPayout::class, 'sub_affiliate_id');
    }

    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(AffiliateWithdrawalRequest::class);
    }

    public function attributedBusinesses(): HasMany
    {
        return $this->hasMany(Business::class, 'referring_affiliate_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function referredBusinesses(): HasMany
    {
        return $this->hasMany(Business::class, 'sponsor_id');
    }

    public function subscribedBusinesses(): HasMany
    {
        return $this->referredBusinesses()->where('subscription_status', SubscriptionStatus::ACTIVE);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(AffiliateCommission::class);
    }

    public function isApproved(): bool
    {
        return $this->status === AffiliateStatus::APPROVED && $this->is_active;
    }

    public function canRefer(): bool
    {
        return $this->isApproved() && ! $this->trashed();
    }

    public function isSubAffiliate(): bool
    {
        return $this->parent_affiliate_id !== null;
    }

    public function isTeamLeader(): bool
    {
        return $this->teamMembers()->exists();
    }

    public function primaryAffiliate(): self
    {
        return $this->parent ?: $this;
    }

    public function effectiveReferralCode(): string
    {
        return $this->primaryAffiliate()->code;
    }

    public function referralUrl(?string $subCode = null): string
    {
        $url = url('/ref/' . $this->effectiveReferralCode());

        $code = $subCode ?: ($this->isSubAffiliate() ? $this->code : null);

        if ($code) {
            $url .= '?sub=' . urlencode($code);
        }

        return $url;
    }

    public function teamInviteUrl(): string
    {
        return url('/affiliate/join/' . $this->code);
    }

    public function totalCommissionEarned(): float
    {
        return (float) $this->commissions()->sum('commission_amount');
    }

    public function isSystemDefault(): bool
    {
        return strtolower((string) $this->code) === strtolower((string) config('affiliates.system_default_code', 'admin'));
    }
}
