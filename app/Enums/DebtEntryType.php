<?php

namespace App\Enums;

class DebtEntryType
{
    public const DEBIT = 'debit';
    public const CREDIT = 'credit';
    public const PAYMENT = 'payment';

    public static function all(): array
    {
        return [
            self::DEBIT,
            self::CREDIT,
            self::PAYMENT,
        ];
    }
}
