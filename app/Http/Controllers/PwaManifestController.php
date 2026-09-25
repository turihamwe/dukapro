<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PwaManifestController extends Controller
{
    public function show(Business $business, Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && (int) $user->business_id === (int) $business->id, 403);

        $startRoute = $user->can('access-pos')
            ? 'tenant.pos.index'
            : 'tenant.downloads.index';

        $startUrl = route($startRoute, ['business' => $business->slug], true);
        $scope = url('/app/'.$business->slug.'/');

        $brand = platform_brand('name');

        return response()->json([
            'name' => $brand.' POS',
            'short_name' => $brand,
            'description' => $brand.' point of sale and business management',
            'id' => '/app/'.$business->slug.'/',
            'start_url' => $startUrl,
            'scope' => $scope,
            'display' => 'standalone',
            'orientation' => 'any',
            'background_color' => '#0A192F',
            'theme_color' => '#0A192F',
            'icons' => [
                [
                    'src' => asset('assets/dukapro-logo.png'),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => asset('assets/dukapro-logo.png'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => asset('assets/dukapro-logo2.png'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
            ],
        ])->header('Content-Type', 'application/manifest+json');
    }
}
