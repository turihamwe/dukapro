<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierCreditPayment extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'business_id',
        'supplier_id',
        'supplier_credit_purchase_id',
        'user_id',
        'amount',
        'paid_at',
        'payment_method',
        'reference',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(SupplierCreditPurchase::class, 'supplier_credit_purchase_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
