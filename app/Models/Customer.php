<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'name',
        'company_name',
        'phone',
        'email',
        'address',
        'notes',
        'credit_limit',
        'outstanding_balance',
        'is_active',
        'is_credit_customer',
    ];

    protected $casts = [
        'credit_limit' => 'float',
        'outstanding_balance' => 'float',
        'is_active' => 'boolean',
        'is_credit_customer' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function debtEntries(): HasMany
    {
        return $this->hasMany(DebtLedgerEntry::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
