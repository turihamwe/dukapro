<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Damage extends Model
{
    use BelongsToBranch, BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'branch_id',
        'product_id',
        'user_id',
        'quantity',
        'reason',
        'cost_price',
        'damage_date',
    ];

    protected $casts = [
        'quantity' => 'float',
        'cost_price' => 'float',
        'damage_date' => 'date',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lossValue(): float
    {
        return round($this->quantity * $this->cost_price, 2);
    }
}
