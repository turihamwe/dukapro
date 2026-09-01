<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateCommission extends Model
{
    protected $fillable = [
        'affiliate_id',
        'business_id',
        'subscription_payment_id',
        'payment_amount',
        'commission_rate',
        'commission_amount',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'payment_amount' => 'float',
        'commission_rate' => 'float',
        'commission_amount' => 'float',
        'paid_at' => 'datetime',
    ];

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function subscriptionPayment(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPayment::class);
    }
}
