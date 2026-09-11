<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateTeamPayout extends Model
{
    protected $fillable = [
        'parent_affiliate_id',
        'sub_affiliate_id',
        'amount',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function parentAffiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class, 'parent_affiliate_id');
    }

    public function subAffiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class, 'sub_affiliate_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
