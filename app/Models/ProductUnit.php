<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductUnit extends Model
{
    protected $fillable = [
        'product_id',
        'unit_name',
        'conversion_factor',
        'is_base_unit',
        'price',
        'sort_order',
    ];

    protected $casts = [
        'conversion_factor' => 'float',
        'is_base_unit' => 'boolean',
        'price' => 'float',
        'sort_order' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function sellingPrice(Product $product): float
    {
        if ($this->price !== null && $this->price > 0) {
            return (float) $this->price;
        }

        return (float) $product->price * (float) $this->conversion_factor;
    }

    public function toBaseQuantity(float $quantity): float
    {
        return round($quantity * (float) $this->conversion_factor, 3);
    }

    public function maxSellableQuantity(float $baseStock): float
    {
        $factor = (float) $this->conversion_factor;

        if ($factor <= 0) {
            return 0;
        }

        return round($baseStock / $factor, 3);
    }
}
