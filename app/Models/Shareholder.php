<?php

namespace App\Models;

use App\Enums\ShareholderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shareholder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'national_id',
        'shares_owned',
        'capital_invested',
        'total_earnings',
        'status',
        'is_active',
        'contract_completed',
        'contract_completed_at',
        'registered_at',
        'application_message',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'shares_owned' => 'float',
        'capital_invested' => 'float',
        'total_earnings' => 'float',
        'is_active' => 'boolean',
        'contract_completed' => 'boolean',
        'contract_completed_at' => 'datetime',
        'registered_at' => 'datetime',
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

    public function earnings(): HasMany
    {
        return $this->hasMany(ShareholderEarning::class);
    }

    public function earningsCap(): float
    {
        return round((float) $this->capital_invested * config('shareholders.earnings_cap_multiplier', 3), 2);
    }

    public function remainingEarningsCapacity(): float
    {
        return max(0, round($this->earningsCap() - (float) $this->total_earnings, 2));
    }

    public function earningsProgressPercent(): float
    {
        $cap = $this->earningsCap();
        if ($cap <= 0) {
            return 0;
        }

        return min(100, round(((float) $this->total_earnings / $cap) * 100, 1));
    }

    public function isContractComplete(): bool
    {
        return (bool) $this->contract_completed
            || $this->status === ShareholderStatus::COMPLETED
            || ((float) $this->total_earnings >= $this->earningsCap() && $this->earningsCap() > 0);
    }

    public function countsTowardAllocation(): bool
    {
        return in_array($this->status, ShareholderStatus::allocated(), true);
    }
}
