<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'business_id',
        'name',
        'phone',
        'email',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function creditPurchases(): HasMany
    {
        return $this->hasMany(SupplierCreditPurchase::class);
    }

    public function openBalance(): float
    {
        return (float) $this->creditPurchases()
            ->whereIn('status', [SupplierCreditPurchase::STATUS_OPEN, SupplierCreditPurchase::STATUS_PARTIAL])
            ->get()
            ->sum(fn (SupplierCreditPurchase $purchase) => $purchase->balanceDue());
    }
}
