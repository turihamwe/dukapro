<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItemBatchAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_item_id',
        'product_batch_id',
        'quantity',
        'cost_price',
        'selling_price',
        'subtotal',
        'is_legacy_stock',
    ];

    protected $casts = [
        'quantity' => 'float',
        'cost_price' => 'float',
        'selling_price' => 'float',
        'subtotal' => 'float',
        'is_legacy_stock' => 'boolean',
    ];

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function productBatch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class);
    }
}
