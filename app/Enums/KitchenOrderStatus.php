<?php

namespace App\Enums;

class KitchenOrderStatus
{
    public const PENDING_KITCHEN = 'pending_kitchen';
    public const PREPARING = 'preparing';
    public const READY = 'ready';
    public const COMPLETED = 'completed';
    public const CANCELLED = 'cancelled';

    public static function all(): array
    {
        return [
            self::PENDING_KITCHEN,
            self::PREPARING,
            self::READY,
            self::COMPLETED,
            self::CANCELLED,
        ];
    }

    public static function active(): array
    {
        return [
            self::PENDING_KITCHEN,
            self::PREPARING,
            self::READY,
        ];
    }

    public static function label(string $status): string
    {
        return [
            self::PENDING_KITCHEN => 'Pending (Kitchen)',
            self::PREPARING => 'Preparing',
            self::READY => 'Ready',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        ][$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    public static function canTransition(string $from, string $to): bool
    {
        $allowed = [
            self::PENDING_KITCHEN => [self::PREPARING, self::CANCELLED],
            self::PREPARING => [self::READY, self::CANCELLED],
            self::READY => [self::COMPLETED, self::CANCELLED],
            self::COMPLETED => [],
            self::CANCELLED => [],
        ];

        return in_array($to, $allowed[$from] ?? [], true);
    }
}
