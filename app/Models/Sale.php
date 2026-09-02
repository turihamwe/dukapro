<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use BelongsToBranch, BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'branch_id',
        'user_id',
        'waiter_id',
        'customer_id',
        'sale_number',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total',
        'payment_method',
        'mobile_money_provider',
        'is_credit_sale',
        'status',
        'notes',
        'completed_at',
        'credit_settled_at',
        'credit_settlement_method',
        'credit_settlement_notes',
    ];

    protected $casts = [
        'subtotal' => 'float',
        'tax_amount' => 'float',
        'discount_amount' => 'float',
        'total' => 'float',
        'is_credit_sale' => 'boolean',
        'completed_at' => 'datetime',
        'credit_settled_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function waiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waiter_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}
