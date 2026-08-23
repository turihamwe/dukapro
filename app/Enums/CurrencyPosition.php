<?php

namespace App\Enums;

class CurrencyPosition
{
    public const PREFIX = 'prefix';
    public const SUFFIX = 'suffix';

    public static function all(): array
    {
        return [
            self::PREFIX,
            self::SUFFIX,
        ];
    }
}
