<?php

use App\Models\Business;

if (! function_exists('tenant_route')) {
    function tenant_route(string $name, array $parameters = [], bool $absolute = true): string
    {
        $user = auth()->user();

        if (! $user || ! $user->business) {
            throw new RuntimeException('Cannot generate tenant route without an authenticated business user.');
        }

        return route($name, array_merge(['business' => $user->business->slug], $parameters), $absolute);
    }
}

if (! function_exists('tenant_slug')) {
    function tenant_slug(): string
    {
        $user = auth()->user();

        return $user && $user->business ? $user->business->slug : '';
    }
}

if (! function_exists('active_business')) {
    function active_business(): ?Business
    {
        $user = auth()->user();

        return $user ? $user->business : null;
    }
}

if (! function_exists('format_money')) {
    /**
     * Format an amount using the active business currency settings.
     * Prefix:  "UGX 100,000"   Suffix: "100,000/="
     */
    function format_money($amount, ?Business $business = null, int $decimals = 0): string
    {
        $business = $business ?? active_business();
        $formatted = number_format((float) $amount, $decimals, '.', ',');

        if (! $business) {
            return $formatted;
        }

        $symbol = $business->currency_symbol ?? 'UGX';
        $position = $business->currency_position ?? 'prefix';

        if ($position === 'suffix') {
            return $formatted . $symbol;
        }

        return $symbol . ' ' . $formatted;
    }
}
