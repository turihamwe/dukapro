<?php

use App\Models\Business;

if (! function_exists('business_portal_url')) {
    function business_portal_url(?Business $business = null): string
    {
        $business = $business ?? active_business();

        if (! $business) {
            return route('portal');
        }

        return $business->portalLoginUrl();
    }
}

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

if (! function_exists('variant_attributes_for_form')) {
    /**
     * Map stored variant_attributes JSON to friendly form fields.
     */
    function variant_attributes_for_form(?array $attributes): array
    {
        if (empty($attributes)) {
            return ['name' => '', 'values' => ''];
        }

        if (count($attributes) === 1) {
            $name = array_key_first($attributes);
            $value = $attributes[$name];

            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            return [
                'name' => (string) $name,
                'values' => (string) $value,
            ];
        }

        $pairs = [];
        foreach ($attributes as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $pairs[] = $key . ': ' . $value;
        }

        return [
            'name' => '',
            'values' => implode('; ', $pairs),
        ];
    }
}

if (! function_exists('variant_attributes_from_form')) {
    /**
     * Convert friendly form fields back to variant_attributes JSON.
     */
    function variant_attributes_from_form(?string $name, ?string $values): ?array
    {
        $name = trim((string) ($name ?? ''));
        $values = trim((string) ($values ?? ''));

        if ($name === '' && $values === '') {
            return null;
        }

        if ($name !== '' && $values !== '') {
            return [$name => $values];
        }

        if ($name !== '') {
            return null;
        }

        $result = [];
        foreach (preg_split('/\s*;\s*/', $values) as $segment) {
            $segment = trim($segment);
            if ($segment === '' || strpos($segment, ':') === false) {
                continue;
            }

            [$key, $val] = array_map('trim', explode(':', $segment, 2));
            if ($key !== '') {
                $result[$key] = $val;
            }
        }

        return empty($result) ? null : $result;
    }
}

if (! function_exists('format_variant_attributes')) {
    /**
     * Human-readable variant summary for lists and tables.
     */
    function format_variant_attributes(?array $attributes): ?string
    {
        if (empty($attributes)) {
            return null;
        }

        $parts = [];
        foreach ($attributes as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $parts[] = $key . ': ' . $value;
        }

        return implode(' · ', $parts);
    }
}
