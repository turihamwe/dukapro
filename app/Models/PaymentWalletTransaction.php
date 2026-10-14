<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\WalletInflowCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentWalletTransaction extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    public const DIRECTION_IN = 'in';

    public const DIRECTION_OUT = 'out';

    protected $fillable = [
        'business_id',
        'payment_wallet_id',
        'user_id',
        'direction',
        'source_type',
        'amount',
        'balance_after',
        'category',
        'source_name',
        'reference',
        'notes',
        'occurred_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'occurred_at' => 'datetime',
    ];

    public function isInflow(): bool
    {
        return $this->direction === self::DIRECTION_IN;
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(PaymentWallet::class, 'payment_wallet_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categoryLabel(): string
    {
        return WalletInflowCategory::label($this->category);
    }

    public function sourceLabel(): string
    {
        return \App\Support\WalletTransactionSource::label($this->source_type, $this->category);
    }

    public function displayNotes(): ?string
    {
        return $this->notes ?: $this->source_name;
    }
}
