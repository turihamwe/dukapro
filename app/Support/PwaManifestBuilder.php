<?php

namespace App\Support;

use App\Models\Business;
use App\Models\User;

class PwaManifestBuilder
{
    public const THEME_COLOR = '#0A192F';

    public const BACKGROUND_COLOR = '#0A192F';

    /**
     * @return array<string, mixed>
     */
    public static function forSite(): array
    {
        $brand = platform_brand('name');
        $startUrl = url('/login');

        return self::basePayload(
            name: $brand.' POS',
            shortName: $brand,
            description: $brand.' point of sale and business management',
            id: '/',
            startUrl: $startUrl,
            scope: url('/')
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function forTenant(Business $business, User $user): array
    {
        $brand = platform_brand('name');
        $slug = $business->slug;
        $scope = url('/app/'.$slug.'/');
        $startUrl = self::resolveTenantStartUrl($business, $user);

        return self::basePayload(
            name: $brand.' POS',
            shortName: $brand,
            description: $brand.' point of sale and business management',
            id: '/app/'.$slug.'/',
            startUrl: $startUrl,
            scope: $scope
        );
    }

    protected static function resolveTenantStartUrl(Business $business, User $user): string
    {
        if ($user->can('view-dashboard')) {
            return route('tenant.dashboard', ['business' => $business->slug], true);
        }

        if ($user->can('access-pos')) {
            return route('tenant.pos.index', ['business' => $business->slug], true);
        }

        return route('tenant.downloads.index', ['business' => $business->slug], true);
    }

    /**
     * @return array<string, mixed>
     */
    protected static function basePayload(
        string $name,
        string $shortName,
        string $description,
        string $id,
        string $startUrl,
        string $scope
    ): array {
        return [
            'name' => $name,
            'short_name' => $shortName,
            'description' => $description,
            'id' => $id,
            'start_url' => $startUrl,
            'scope' => $scope,
            'display' => 'standalone',
            'orientation' => 'any',
            'background_color' => self::BACKGROUND_COLOR,
            'theme_color' => self::THEME_COLOR,
            'icons' => self::icons(),
        ];
    }

    /**
     * @return list<array<string, string>>
     */
    public static function icons(): array
    {
        return [
            [
                'src' => asset('assets/pwa/icon-192.png'),
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => asset('assets/pwa/icon-512.png'),
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => asset('assets/pwa/icon-512.png'),
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'maskable',
            ],
        ];
    }
}
