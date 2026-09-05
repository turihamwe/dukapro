<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessModule extends Model
{
    protected $fillable = [
        'business_id',
        'module_key',
        'enabled',
        'settings',
        'source',
        'billing_comped',
        'billing_subscribed_until',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'settings' => 'array',
        'billing_comped' => 'boolean',
        'billing_subscribed_until' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
