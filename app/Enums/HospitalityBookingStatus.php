<?php

namespace App\Enums;

class HospitalityBookingStatus
{
    public const RESERVED = 'reserved';

    public const CHECKED_IN = 'checked_in';

    public const CHECKED_OUT = 'checked_out';

    public const CANCELLED = 'cancelled';

    public static function all(): array
    {
        return [
            self::RESERVED,
            self::CHECKED_IN,
            self::CHECKED_OUT,
            self::CANCELLED,
        ];
    }

    public static function labels(): array
    {
        return [
            self::RESERVED => 'Reserved',
            self::CHECKED_IN => 'Checked in',
            self::CHECKED_OUT => 'Checked out',
            self::CANCELLED => 'Cancelled',
        ];
    }

    public static function label(?string $status): string
    {
        return self::labels()[$status] ?? ucfirst(str_replace('_', ' ', (string) $status));
    }

    /**
     * Statuses that hold the room on the calendar (block double booking).
     *
     * @return list<string>
     */
    public static function blocking(): array
    {
        return [
            self::RESERVED,
            self::CHECKED_IN,
        ];
    }
}
