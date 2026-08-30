<?php

namespace App\Support;

use InvalidArgumentException;

class SubscriptionPlan
{
    public static function all(): array
    {
        return config('subscription.plans', []);
    }

    public static function keys(): array
    {
        return array_keys(static::all());
    }

    public static function isValid(string $key): bool
    {
        return array_key_exists($key, static::all());
    }

    public static function find(string $key): array
    {
        $plan = static::all()[$key] ?? null;

        if (! $plan) {
            throw new InvalidArgumentException('Invalid subscription plan: ' . $key);
        }

        return $plan;
    }

    public static function defaultKey(): string
    {
        $default = config('subscription.default_plan', 'monthly');

        return static::isValid($default) ? $default : 'monthly';
    }
}
