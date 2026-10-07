<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentWallet extends Model
{
    use BelongsToTenant;

    public const TYPE_CASH = 'cash';

    public const TYPE_MOBILE_MONEY = 'mobile_money';

    public const TYPE_BANK = 'bank';

    protected $fillable = [
        'business_id',
        'name',
        'type',
        'opening_balance',
        'current_balance',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'opening_balance' => 'float',
        'current_balance' => 'float',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public static function types(): array
    {
        return [
            self::TYPE_CASH => 'Cash',
            self::TYPE_MOBILE_MONEY => 'Mobile money',
            self::TYPE_BANK => 'Bank account',
        ];
    }

    public function typeLabel(): string
    {
        return self::types()[$this->type] ?? ucfirst(str_replace('_', ' ', $this->type));
    }

    public function debtLedgerPayments(): HasMany
    {
        return $this->hasMany(DebtLedgerEntry::class, 'payment_wallet_id');
    }

    public function supplierCreditPayments(): HasMany
    {
        return $this->hasMany(SupplierCreditPayment::class, 'payment_wallet_id');
    }
}
