<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierCreditPurchaseLine extends Model
{
    protected $fillable = [
        'supplier_credit_purchase_id',
        'product_id',
        'quantity',
        'unit_cost',
        'line_total',
        'attribute_values',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'line_total' => 'decimal:2',
        'attribute_values' => 'array',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(SupplierCreditPurchase::class, 'supplier_credit_purchase_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
