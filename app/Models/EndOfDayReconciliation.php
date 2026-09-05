<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EndOfDayReconciliation extends Model
{
    use BelongsToBranch, BelongsToTenant, HasFactory;

    protected $fillable = [
        'business_id',
        'branch_id',
        'user_id',
        'reconciliation_date',
        'expected_cash',
        'expected_mobile_money',
        'expected_bank_other',
        'actual_cash',
        'actual_mobile_money',
        'actual_bank_other',
        'cash_variance',
        'mobile_variance',
        'missing_money',
        'total_sales',
        'total_expenses',
        'total_damages',
        'extra_cash',
        'net_income',
        'notes',
        'status',
    ];

    protected $casts = [
        'reconciliation_date' => 'date',
        'expected_cash' => 'float',
        'expected_mobile_money' => 'float',
        'expected_bank_other' => 'float',
        'actual_cash' => 'float',
        'actual_mobile_money' => 'float',
        'actual_bank_other' => 'float',
        'cash_variance' => 'float',
        'mobile_variance' => 'float',
        'missing_money' => 'float',
        'total_sales' => 'float',
        'total_expenses' => 'float',
        'total_damages' => 'float',
        'extra_cash' => 'float',
        'net_income' => 'float',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function waiterBalances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ShiftWaiterBalance::class, 'end_of_day_reconciliation_id');
    }

    public function shortages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ReconciliationShortage::class, 'end_of_day_reconciliation_id');
    }
}
