<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierCreditPurchase extends Model
{
    use BelongsToTenant;

    public const STATUS_OPEN = 'open';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'business_id',
        'supplier_id',
        'branch_id',
        'user_id',
        'reference',
        'purchase_date',
        'total_amount',
        'amount_paid',
        'status',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SupplierCreditPurchaseLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierCreditPayment::class);
    }

    public function balanceDue(): float
    {
        return max(0, round((float) $this->total_amount - (float) $this->amount_paid, 2));
    }

    public function refreshPaymentStatus(): void
    {
        $paid = (float) $this->amount_paid;
        $total = (float) $this->total_amount;

        if ($paid <= 0) {
            $this->status = self::STATUS_OPEN;
        } elseif ($paid >= $total) {
            $this->status = self::STATUS_PAID;
            $this->amount_paid = $total;
        } else {
            $this->status = self::STATUS_PARTIAL;
        }

        $this->save();
    }
}
