<?php

namespace App\Enums;

class MeasurementUnit
{
    public const PIECE = 'piece';
    public const KG = 'kg';
    public const GRAM = 'g';
    public const LITER = 'liter';
    public const MILLILITER = 'ml';
    public const BOX = 'box';
    public const PACK = 'pack';
    public const DOZEN = 'dozen';
    public const METER = 'meter';

    public static function all(): array
    {
        return [
            self::PIECE,
            self::KG,
            self::GRAM,
            self::LITER,
            self::MILLILITER,
            self::BOX,
            self::PACK,
            self::DOZEN,
            self::METER,
        ];
    }
}
