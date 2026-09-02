<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftWaiterBalance extends Model
{
    use BelongsToTenant;

    public const STATUS_OPEN = 'open';

    public const STATUS_BALANCED = 'balanced';

    public const STATUS_SUBMITTED = 'submitted';

    protected $fillable = [
        'business_id',
        'shift_date',
        'cashier_user_id',
        'waiter_user_id',
        'expected_cash',
        'expected_mobile_airtel',
        'expected_mobile_mtn',
        'expected_bank_other',
        'expected_credit',
        'actual_cash',
        'actual_mobile_airtel',
        'actual_mobile_mtn',
        'actual_bank_other',
        'actual_credit_collected',
        'shortage',
        'notes',
        'status',
        'end_of_day_reconciliation_id',
    ];

    protected $casts = [
        'shift_date' => 'date',
        'expected_cash' => 'float',
        'expected_mobile_airtel' => 'float',
        'expected_mobile_mtn' => 'float',
        'expected_bank_other' => 'float',
        'expected_credit' => 'float',
        'actual_cash' => 'float',
        'actual_mobile_airtel' => 'float',
        'actual_mobile_mtn' => 'float',
        'actual_bank_other' => 'float',
        'actual_credit_collected' => 'float',
        'shortage' => 'float',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_user_id');
    }

    public function waiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waiter_user_id');
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(EndOfDayReconciliation::class, 'end_of_day_reconciliation_id');
    }

    public function expectedTotal(): float
    {
        return round(
            $this->expected_cash
            + $this->expected_mobile_airtel
            + $this->expected_mobile_mtn
            + $this->expected_bank_other
            + $this->expected_credit,
            2
        );
    }

    public function actualTotal(): float
    {
        return round(
            $this->actual_cash
            + $this->actual_mobile_airtel
            + $this->actual_mobile_mtn
            + $this->actual_bank_other
            + $this->actual_credit_collected,
            2
        );
    }
}
