<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateWithdrawalRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PAID = 'paid';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'affiliate_id',
        'amount',
        'status',
        'payout_method',
        'payout_account',
        'notes',
        'processed_at',
        'processed_by',
    ];

    protected $casts = [
        'amount' => 'float',
        'processed_at' => 'datetime',
    ];

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
