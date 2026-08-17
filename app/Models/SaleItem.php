<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'product_name',
        'sku',
        'variant_attributes',
        'measurement_unit',
        'quantity',
        'unit_price',
        'discount_amount',
        'subtotal',
    ];

    protected $casts = [
        'variant_attributes' => 'array',
        'quantity' => 'float',
        'unit_price' => 'float',
        'discount_amount' => 'float',
        'subtotal' => 'float',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
