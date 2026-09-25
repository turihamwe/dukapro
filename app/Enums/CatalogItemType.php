<?php

namespace App\Enums;

use App\Models\Business;
use App\Support\BusinessModeCompliance;

class CatalogItemType
{
    public const PHYSICAL = 'physical';

    public const SERVICE = 'service';

    public const RENTABLE = 'rentable';

    public static function all(): array
    {
        return [
            self::PHYSICAL,
            self::SERVICE,
            self::RENTABLE,
        ];
    }

    public static function labels(): array
    {
        return [
            self::PHYSICAL => 'Physical product (retail stock)',
            self::SERVICE => 'Service (fee / labour — no stock)',
            self::RENTABLE => 'Rentable item (hire / rental rate)',
        ];
    }

    public static function label(?string $type): string
    {
        if ($type === null || $type === '' || $type === 'inventory_only') {
            return self::labels()[self::PHYSICAL];
        }

        return self::labels()[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }

    /**
     * @return list<string>
     */
    public static function allowedFor(?Business $business): array
    {
        $types = [self::PHYSICAL];

        if ($business && BusinessModeCompliance::serviceCatalogActive($business)) {
            $types[] = self::SERVICE;
        }

        if ($business && BusinessModeCompliance::rentalModeActive($business)) {
            $types[] = self::RENTABLE;
        }

        if ($business && BusinessModeCompliance::hospitalityRentableCatalogActive($business)) {
            if (! in_array(self::RENTABLE, $types, true)) {
                $types[] = self::RENTABLE;
            }
        }

        return $types;
    }

    public static function defaultFor(?Business $business): string
    {
        $allowed = self::allowedFor($business);

        if ($business) {
            if ($business->operating_mode === BusinessOperatingMode::SERVICE_BASED && in_array(self::SERVICE, $allowed, true)) {
                return self::SERVICE;
            }
            if ($business->operating_mode === BusinessOperatingMode::RENTAL && in_array(self::RENTABLE, $allowed, true)) {
                return self::RENTABLE;
            }
        }

        return $allowed[0] ?? self::PHYSICAL;
    }

    public static function tracksStock(string $type): bool
    {
        return in_array($type, [self::PHYSICAL, self::RENTABLE], true);
    }

    public static function requiresSellingPrice(string $type): bool
    {
        return in_array($type, [self::PHYSICAL, self::SERVICE, self::RENTABLE], true);
    }

    public static function allowsVariants(string $type): bool
    {
        return $type === self::PHYSICAL;
    }

    public static function rentalRateUnits(): array
    {
        return [
            'hour' => 'Per hour',
            'day' => 'Per day',
            'week' => 'Per week',
            'month' => 'Per month',
        ];
    }

    public static function labelsFor(?Business $business): array
    {
        $labels = self::labels();

        if ($business && BusinessModeCompliance::hospitalityModeActive($business)) {
            $labels[self::RENTABLE] = 'Room / rentable asset';
        }

        return $labels;
    }
}
