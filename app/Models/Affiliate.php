<?php

namespace App\Models;

use App\Enums\AffiliateStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Affiliate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'code',
        'commission_rate',
        'status',
        'is_active',
        'application_message',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'commission_rate' => 'float',
        'is_active' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function referredBusinesses(): HasMany
    {
        return $this->hasMany(Business::class, 'sponsor_id');
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

    public function referralUrl(): string
    {
        return route('register', ['ref' => $this->code]);
    }

    public function totalCommissionEarned(): float
    {
        return (float) $this->commissions()->sum('commission_amount');
    }
}
