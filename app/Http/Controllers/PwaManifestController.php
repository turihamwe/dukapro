<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Support\PwaManifestBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PwaManifestController extends Controller
{
    public function site(): JsonResponse
    {
        return $this->manifestResponse(PwaManifestBuilder::forSite());
    }

    public function show(Business $business, Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && (int) $user->business_id === (int) $business->id, 403);

        return $this->manifestResponse(PwaManifestBuilder::forTenant($business, $user));
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    protected function manifestResponse(array $manifest): JsonResponse
    {
        return response()
            ->json($manifest)
            ->header('Content-Type', 'application/manifest+json; charset=utf-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
