<?php

namespace App\Support;

class WalletTransactionSource
{
    public const MANUAL_DEPOSIT = 'manual_deposit';

    public const RECEIVABLE_PAYMENT = 'receivable_payment';

    public const RECEIVABLE_REVERSAL = 'receivable_reversal';

    public const VENDOR_PAYMENT = 'vendor_payment';

    public const VENDOR_PAYMENT_REVERSAL = 'vendor_payment_reversal';

    public const EXPENSE = 'expense';

    public const ADJUSTMENT_IN = 'adjustment_in';

    public const ADJUSTMENT_OUT = 'adjustment_out';

    public static function labels(): array
    {
        return [
            self::MANUAL_DEPOSIT => 'Direct deposit',
            self::RECEIVABLE_PAYMENT => 'Receivable payment',
            self::RECEIVABLE_REVERSAL => 'Receivable reversal',
            self::VENDOR_PAYMENT => 'Vendor payment',
            self::VENDOR_PAYMENT_REVERSAL => 'Vendor payment reversal',
            self::EXPENSE => 'Operating expense',
            self::ADJUSTMENT_IN => 'Funds added',
            self::ADJUSTMENT_OUT => 'Funds removed',
        ];
    }

    public static function label(?string $sourceType, ?string $categoryFallback = null): string
    {
        if ($sourceType && isset(self::labels()[$sourceType])) {
            return self::labels()[$sourceType];
        }

        if ($categoryFallback && array_key_exists($categoryFallback, WalletInflowCategory::all())) {
            return WalletInflowCategory::label($categoryFallback);
        }

        if ($categoryFallback) {
            return ucfirst(str_replace('_', ' ', $categoryFallback));
        }

        return 'Wallet movement';
    }
}
