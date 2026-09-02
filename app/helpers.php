<?php

use App\Models\Business;

if (! function_exists('platform_brand')) {
    function platform_brand(?string $key = null)
    {
        $appName = config('app.name', 'Duka Pro');
        if (trim((string) $appName) === '' || strcasecmp(trim((string) $appName), 'Laravel') === 0) {
            $appName = 'Duka Pro';
        }

        $storedName = \App\Models\SystemSetting::get('company_name', $appName);
        if (trim((string) $storedName) === '' || strcasecmp(trim((string) $storedName), 'Laravel') === 0) {
            $storedName = 'Duka Pro';
        }

        $brand = [
            'name' => $storedName,
            'tagline' => \App\Models\SystemSetting::get('company_tagline', 'Empowering African businesses with smart management'),
            'logo_url' => ($path = \App\Models\SystemSetting::get('company_logo_path'))
                ? asset('storage/' . ltrim($path, '/'))
                : null,
        ];

        return $key ? ($brand[$key] ?? null) : $brand;
    }
}

if (! function_exists('platform_footer_tagline')) {
    function platform_footer_tagline(): string
    {
        $tagline = trim((string) platform_brand('tagline'));
        $legacy = [
            "Trusted by 5000+ businesses",
            "Trusted by 5000+ Businesses",
            "Manage your Business From Anywhere",
            "LET'S GO DIGITAL",
        ];

        if ($tagline === '' || in_array($tagline, $legacy, true)) {
            return 'Empowering African businesses with smart management';
        }

        return $tagline;
    }
}

if (! function_exists('dukapro_logo_url')) {
    /**
     * Official DukaPro logo from public/assets, then SuperAdmin upload, else null.
     */
    function dukapro_logo_url(): ?string
    {
        static $cached = null;

        if ($cached !== null) {
            return $cached ?: null;
        }

        $candidates = [
            'assets/dukapro-logo.png',
            'assets/dukapro-logo.jpg',
            'assets/dukapro-logo.jpeg',
            'assets/dukapro-logo.webp',
            'assets/dukapro-logo.svg',
            'assets/logo.png',
            'assets/logo.svg',
        ];

        foreach ($candidates as $path) {
            if (is_file(public_path($path))) {
                $cached = asset($path) . '?v=' . filemtime(public_path($path));

                return $cached;
            }
        }

        if ($uploaded = platform_brand('logo_url')) {
            $cached = $uploaded;

            return $cached;
        }

        $cached = false;

        return null;
    }
}

if (! function_exists('user_ui_theme')) {
    function user_ui_theme(): string
    {
        $user = auth()->user();

        if (! $user) {
            return 'plain';
        }

        if ($user->ui_theme === 'custom') {
            return 'modern';
        }

        return in_array($user->ui_theme, ['plain', 'modern'], true)
            ? $user->ui_theme
            : 'plain';
    }
}

if (! function_exists('ui_theme_label')) {
    function ui_theme_label(string $theme): string
    {
        return [
            'plain' => 'Plain Theme',
            'modern' => platform_brand('name') . ' Modern',
            'custom' => platform_brand('name') . ' Modern',
        ][$theme] ?? 'Plain Theme';
    }
}

if (! function_exists('format_money_compact')) {
    function format_money_compact($amount, ?Business $business = null): string
    {
        $amount = (float) $amount;
        $business = $business ?? active_business();
        $symbol = $business->currency_symbol ?? 'UGX';

        if ($amount >= 1000000) {
            return $symbol . ' ' . rtrim(rtrim(number_format($amount / 1000000, 1), '0'), '.') . 'M';
        }

        if ($amount >= 1000) {
            return $symbol . ' ' . rtrim(rtrim(number_format($amount / 1000, 1), '0'), '.') . 'K';
        }

        return format_money($amount, $business);
    }
}

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

if (! function_exists('show_subscription_expired_overlay')) {
    function show_subscription_expired_overlay(): bool
    {
        $user = auth()->user();

        if (! $user || ! $user->business || ! $user->can('manage-billing')) {
            return false;
        }

        return $user->business->isSubscriptionExpired();
    }
}

if (! function_exists('format_unit_quantity')) {
    function format_unit_quantity(float $quantity, string $unit, ?int $businessId = null): string
    {
        return \App\Support\MeasurementUnitLabel::formatQuantity($quantity, $unit, $businessId);
    }
}

if (! function_exists('whatsapp_support_digits')) {
    function whatsapp_support_digits(): ?string
    {
        $phone = trim((string) \App\Models\SystemSetting::get('support_phone', ''));
        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 9) {
            return '256' . $digits;
        }

        if (strlen($digits) === 10 && $digits[0] === '0') {
            return '256' . substr($digits, 1);
        }

        return $digits;
    }
}

if (! function_exists('whatsapp_support_url')) {
    function whatsapp_support_url(?string $message = null): string
    {
        $digits = whatsapp_support_digits() ?: '256755825974';
        $url = 'https://wa.me/' . $digits;

        if ($message) {
            $url .= '?text=' . rawurlencode($message);
        }

        return $url;
    }
}

if (! function_exists('whatsapp_float_enabled')) {
    function whatsapp_float_enabled(): bool
    {
        return \App\Models\SystemSetting::get('whatsapp_float_enabled', '1') === '1'
            && whatsapp_support_digits() !== null;
    }
}

if (! function_exists('should_show_whatsapp_float')) {
    function should_show_whatsapp_float(): bool
    {
        if (! whatsapp_float_enabled()) {
            return false;
        }

        if (request()->routeIs('superadmin.*')) {
            return false;
        }

        return auth()->check() || request()->routeIs('login', 'register', 'login.*', 'register.*');
    }
}
