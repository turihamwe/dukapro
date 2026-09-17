<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Helpers\SystemAuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Support\VariablePricingMode;
use Illuminate\Http\Request;

class BusinessVariablePricingController extends Controller
{
    public function update(Request $request, int $businessId)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $business = Business::query()->findOrFail($businessId);

        if (! VariablePricingMode::platformEnabled()) {
            return redirect()
                ->route('superadmin.entities.show', ['businesses', $business->id, 'tab' => 'details'])
                ->withErrors(['variable_pricing' => 'Enable Variable pricing in System Settings first.']);
        }

        $request->validate([
            'variable_pricing_enabled' => 'nullable|boolean',
        ]);

        $enabled = $request->boolean('variable_pricing_enabled');
        $before = (bool) $business->variable_pricing_enabled;

        $business->update([
            'variable_pricing_enabled' => $enabled,
        ]);

        SystemAuditLogger::record(
            'business_variable_pricing_updated',
            'Superadmin ' . ($enabled ? 'enabled' : 'disabled') . ' variable pricing for ' . $business->name,
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
                ? 'Variable pricing enabled for this business.'
                : 'Variable pricing disabled for this business.');
    }
}
