<?php

namespace App\Support\SuperAdmin;

use App\Support\AffiliateStatusPresenter;
use Carbon\Carbon;

class EntityTableSort
{
    /**
     * @return array<string, string|int|float>
     */
    public static function rowAttributes(object $record, string $entity, array $columns): array
    {
        $attrs = ['id' => (int) $record->id];

        foreach ($columns as $column) {
            $attrs[$column] = self::value($record, $entity, $column);
        }

        return $attrs;
    }

    /**
     * @return string|int|float
     */
    protected static function value(object $record, string $entity, string $column)
    {
        if ($column === 'business_id' && method_exists($record, 'relationLoaded') && $record->relationLoaded('business') && $record->business) {
            return strtolower((string) $record->business->name);
        }

        if ($column === 'affiliate_id' && method_exists($record, 'relationLoaded') && $record->relationLoaded('affiliate') && $record->affiliate) {
            return strtolower((string) $record->affiliate->name);
        }

        if ($entity === 'businesses' && $column === 'affiliate') {
            if ($record->relationLoaded('sponsor') && $record->sponsor) {
                return strtolower((string) $record->sponsor->name);
            }

            return '';
        }

        if ($entity === 'affiliates' && $column === 'status') {
            return strtolower(AffiliateStatusPresenter::for($record)['label']);
        }

        if ($column === 'shareholder_id' && method_exists($record, 'relationLoaded') && $record->relationLoaded('shareholder') && $record->shareholder) {
            return strtolower((string) $record->shareholder->name);
        }

        $raw = data_get($record, $column);

        if ($raw instanceof Carbon) {
            return $raw->timestamp;
        }

        if (is_bool($raw)) {
            return $raw ? 1 : 0;
        }

        if (is_numeric($raw)) {
            return $raw + 0;
        }

        return strtolower((string) $raw);
    }
}
