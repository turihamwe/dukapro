<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchStockTransfer extends Model
{
    protected $fillable = [
        'business_id',
        'from_branch_id',
        'to_branch_id',
        'from_product_id',
        'to_product_id',
        'user_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'float',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function fromBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    public function fromProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'from_product_id');
    }

    public function toProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'to_product_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
