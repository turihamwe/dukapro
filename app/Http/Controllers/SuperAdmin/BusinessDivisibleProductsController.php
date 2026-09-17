<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Helpers\SystemAuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Support\DivisibleProductsMode;
use Illuminate\Http\Request;

class BusinessDivisibleProductsController extends Controller
{
    public function update(Request $request, int $businessId)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $business = Business::query()->findOrFail($businessId);

        if (! DivisibleProductsMode::platformEnabled()) {
            return redirect()
                ->route('superadmin.entities.show', ['businesses', $business->id, 'tab' => 'details'])
                ->withErrors(['divisible_products' => 'Enable Divisible products in System Settings first.']);
        }

        $request->validate([
            'divisible_products_enabled' => 'nullable|boolean',
        ]);

        $enabled = $request->boolean('divisible_products_enabled');
        $before = (bool) $business->divisible_products_enabled;

        $business->update([
            'divisible_products_enabled' => $enabled,
        ]);

        SystemAuditLogger::record(
            'business_divisible_products_updated',
            'Superadmin ' . ($enabled ? 'enabled' : 'disabled') . ' divisible products for ' . $business->name,
            $business->id,
            (int) $request->user()->id,
            [
                'before' => $before,
                'after' => $enabled,
            ]
        );

        return redirect()
            ->route('superadmin.entities.show', ['businesses', $business->id, 'tab' => 'details'])
            ->with('success', $enabled
                ? 'Divisible products enabled for this business.'
                : 'Divisible products disabled for this business.');
    }
}
