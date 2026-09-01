<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductBatch extends Model
{
    use BelongsToTenant, HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_DEPLETED = 'depleted';

    protected $fillable = [
        'business_id',
        'product_id',
        'quantity',
        'remaining_quantity',
        'cost_price',
        'selling_price',
        'received_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'float',
        'remaining_quantity' => 'float',
        'cost_price' => 'float',
        'selling_price' => 'float',
        'received_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(SaleItemBatchAllocation::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('remaining_quantity', '>', 0);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->remaining_quantity > 0;
    }

    public function markDepletedIfEmpty(): void
    {
        if ($this->remaining_quantity <= 0) {
            $this->update([
                'remaining_quantity' => 0,
                'status' => self::STATUS_DEPLETED,
            ]);
        }
    }
}
