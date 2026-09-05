<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReconciliationShortage extends Model
{
    use BelongsToTenant;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SETTLED = 'settled';

    public const STATUS_WAIVED = 'waived';

    public const SOURCE_WAITER_BALANCE = 'waiter_balance';

    public const SOURCE_CASHIER_EOD = 'cashier_eod';

    protected $fillable = [
        'business_id',
        'user_id',
        'shortage_date',
        'amount',
        'amount_settled',
        'status',
        'source',
        'end_of_day_reconciliation_id',
        'shift_waiter_balance_id',
        'notes',
        'recorded_by_user_id',
        'settled_by_user_id',
        'settled_at',
        'settlement_notes',
    ];

    protected $casts = [
        'shortage_date' => 'date',
        'amount' => 'float',
        'amount_settled' => 'float',
        'settled_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(EndOfDayReconciliation::class, 'end_of_day_reconciliation_id');
    }

    public function waiterBalance(): BelongsTo
    {
        return $this->belongsTo(ShiftWaiterBalance::class, 'shift_waiter_balance_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function settledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'settled_by_user_id');
    }

    public function outstandingAmount(): float
    {
        return round(max(0, $this->amount - $this->amount_settled), 2);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING && $this->outstandingAmount() > 0;
    }

    public function sourceLabel(): string
    {
        switch ($this->source) {
            case self::SOURCE_WAITER_BALANCE:
                return 'Waiter shift';
            case self::SOURCE_CASHIER_EOD:
                return 'Cashier EOD';
            default:
                return ucfirst(str_replace('_', ' ', (string) $this->source));
        }
    }
}
