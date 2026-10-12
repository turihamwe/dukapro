<?php

namespace App\Support;

class WalletInflowCategory
{
    public const OWNER_CAPITAL = 'owner_capital';

    public const LOAN = 'loan';

    public const BANK_INTEREST = 'bank_interest';

    public const MISCELLANEOUS = 'miscellaneous';

    public static function all(): array
    {
        return [
            self::OWNER_CAPITAL => 'Owner capital',
            self::LOAN => 'Loan',
            self::BANK_INTEREST => 'Bank interest',
            self::MISCELLANEOUS => 'Miscellaneous inflow',
        ];
    }

    public static function label(string $key): string
    {
        return self::all()[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }
}
