<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestaurantTable extends Model
{
    use BelongsToBranch, BelongsToTenant;

    protected $fillable = [
        'business_id',
        'branch_id',
        'name',
        'code',
        'capacity',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function kitchenOrders(): HasMany
    {
        return $this->hasMany(KitchenOrder::class);
    }

    public function displayLabel(): string
    {
        if ($this->code) {
            return $this->name . ' (' . $this->code . ')';
        }

        return $this->name;
    }
}
