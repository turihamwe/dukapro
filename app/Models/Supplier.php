<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'opening_balance',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'opening_balance' => 'float',
    ];

    public function creditPurchases(): HasMany
    {
        return $this->hasMany(SupplierCreditPurchase::class);
    }

    public function openingBalancePurchase(): HasOne
    {
        return $this->hasOne(SupplierCreditPurchase::class)
            ->where('is_opening_balance', true);
    }

    public function hasOpeningBalanceBill(): bool
    {
        return $this->openingBalancePurchase()->exists();
    }

    public function openBalance(): float
    {
        return (float) $this->creditPurchases()
            ->whereIn('status', [SupplierCreditPurchase::STATUS_OPEN, SupplierCreditPurchase::STATUS_PARTIAL])
            ->get()
            ->sum(fn (SupplierCreditPurchase $purchase) => $purchase->balanceDue());
    }

    /**
     * Open bills oldest first (FIFO): purchase date, then record order.
     */
    public function openPurchasesFifo()
    {
        return $this->creditPurchases()
            ->whereIn('status', [SupplierCreditPurchase::STATUS_OPEN, SupplierCreditPurchase::STATUS_PARTIAL])
            ->orderBy('purchase_date')
            ->orderBy('created_at')
            ->orderBy('id');
    }
}
