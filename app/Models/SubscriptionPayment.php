<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'business_id',
        'amount',
        'payment_method',
        'reference',
        'provider',
        'status',
        'paid_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'float',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
