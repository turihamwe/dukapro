<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\WalletInflowCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentWalletTransaction extends Model
{
    use BelongsToTenant;

    public const DIRECTION_IN = 'in';

    protected $fillable = [
        'business_id',
        'payment_wallet_id',
        'user_id',
        'direction',
        'amount',
        'category',
        'source_name',
        'occurred_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'occurred_at' => 'datetime',
    ];

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
}
