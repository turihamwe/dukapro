<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KitchenOrder extends Model
{
    use BelongsToBranch, BelongsToTenant;

    protected $fillable = [
        'business_id',
        'branch_id',
        'order_number',
        'waiter_id',
        'placed_by_user_id',
        'restaurant_table_id',
        'table_label',
        'status',
        'subtotal',
        'notes',
        'sale_id',
        'placed_at',
        'preparing_at',
        'ready_at',
        'completed_at',
    ];

    protected $casts = [
        'subtotal' => 'float',
        'placed_at' => 'datetime',
        'preparing_at' => 'datetime',
        'ready_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function waiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waiter_id');
    }

    public function placedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'placed_by_user_id');
    }

    public function restaurantTable(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(KitchenOrderItem::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, \App\Enums\KitchenOrderStatus::active(), true);
    }

    public function tableDisplay(): string
    {
        if ($this->table_label) {
            return $this->table_label;
        }

        if ($this->restaurantTable) {
            return $this->restaurantTable->displayLabel();
        }

        return 'Walk-in';
    }

    public function isPaid(): bool
    {
        if (! $this->sale_id) {
            return false;
        }

        $sale = $this->relationLoaded('sale') ? $this->sale : $this->sale()->first();

        if (! $sale) {
            return false;
        }

        if ($sale->is_credit_sale) {
            return $sale->credit_settled_at !== null;
        }

        return true;
    }

    public function awaitsPayment(): bool
    {
        return $this->isActive() && ! $this->sale_id;
    }

    public function isInvoice(): bool
    {
        return ! $this->isPaid();
    }
}
