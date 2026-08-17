<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebtLedgerEntry extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'business_id',
        'customer_id',
        'user_id',
        'sale_id',
        'type',
        'amount',
        'balance_after',
        'description',
        'due_date',
    ];

    protected $casts = [
        'amount' => 'float',
        'balance_after' => 'float',
        'due_date' => 'date',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
