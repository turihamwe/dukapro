<?php

namespace App\Support;

use App\Enums\MeasurementUnit;
use App\Models\SoldByUnit;

class MeasurementUnitLabel
{
    protected static array $standard = [
        MeasurementUnit::PIECE => ['piece', 'pieces'],
        MeasurementUnit::KG => ['kg', 'kg'],
        MeasurementUnit::GRAM => ['g', 'g'],
        MeasurementUnit::LITER => ['liter', 'liters'],
        MeasurementUnit::MILLILITER => ['ml', 'ml'],
        MeasurementUnit::BOX => ['box', 'boxes'],
        MeasurementUnit::PACK => ['pack', 'packs'],
        MeasurementUnit::DOZEN => ['dozen', 'dozens'],
        MeasurementUnit::METER => ['meter', 'meters'],
    ];

    public static function singular(string $unit, ?int $businessId = null): string
    {
        return self::label($unit, $businessId, true);
    }

    public static function plural(string $unit, ?int $businessId = null): string
    {
        return self::label($unit, $businessId, false);
    }

    public static function forQuantity(float $quantity, string $unit, ?int $businessId = null): string
    {
        $useSingular = abs($quantity - 1) < 0.001;

        return self::label($unit, $businessId, $useSingular);
    }

    public static function formatQuantity(float $quantity, string $unit, ?int $businessId = null): string
    {
        $displayQty = abs($quantity - round($quantity)) < 0.001
            ? (string) (int) round($quantity)
            : rtrim(rtrim(number_format($quantity, 3, '.', ''), '0'), '.');

        return $displayQty . ' ' . self::forQuantity($quantity, $unit, $businessId);
    }

    protected static function label(string $unit, ?int $businessId, bool $singular): string
    {
        $unit = trim($unit);

        if (isset(self::$standard[$unit])) {
            return self::$standard[$unit][$singular ? 0 : 1];
        }

        $name = self::customUnitName($unit, $businessId);

        return $singular ? $name : self::pluralizeWord($name);
    }

    protected static function customUnitName(string $unit, ?int $businessId): string
    {
        if ($businessId) {
            $record = SoldByUnit::query()
                ->where('business_id', $businessId)
                ->where(function ($q) use ($unit) {
                    $q->where('slug', $unit)->orWhere('name', $unit);
                })
                ->first();

            if ($record) {
                return $record->name;
            }
        }

        return ucwords(str_replace(['-', '_'], ' ', $unit));
    }

    protected static function pluralizeWord(string $word): string
    {
        $lower = strtolower($word);

        if (in_array($lower, ['kg', 'g', 'ml'], true)) {
            return $word;
        }

        if (preg_match('/(?:s|x|z|ch|sh)$/i', $word)) {
            return $word . 'es';
        }

        if (preg_match('/[^aeiou]y$/i', $word)) {
            return substr($word, 0, -1) . 'ies';
        }

        if (preg_match('/(?:fe|f)$/i', $word)) {
            return preg_replace('/(?:fe|f)$/i', 'ves', $word);
        }

        return $word . 's';
    }
}
