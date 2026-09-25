<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Helpers\SystemAuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Support\BusinessModeCompliance;
use Illuminate\Http\Request;
class BusinessOperatingModesController extends Controller
{
    public function updateUnlocks(Request $request, int $businessId)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $business = Business::query()->findOrFail($businessId);

        $request->validate([
            'service_mode_admin_unlocked' => 'nullable|boolean',
            'rental_mode_admin_unlocked' => 'nullable|boolean',
            'hospitality_mode_admin_unlocked' => 'nullable|boolean',
        ]);

        $before = $business->settings ?? [];

        foreach ([
            'service' => 'service_mode_admin_unlocked',
            'rental' => 'rental_mode_admin_unlocked',
            'hospitality' => 'hospitality_mode_admin_unlocked',
        ] as $mode => $inputKey) {
            if (! $request->has($inputKey)) {
                continue;
            }

            if ($request->boolean($inputKey) && ! BusinessModeCompliance::globallyEnabled($mode)) {
                return redirect()
                    ->route('superadmin.entities.show', ['businesses', $business->id, 'tab' => 'details'])
                    ->withErrors(['modes' => 'Enable the platform master switch for ' . $mode . ' mode in System Settings first.']);
            }

            BusinessModeCompliance::setAdminUnlock($business, $mode, $request->boolean($inputKey));
            $business = $business->fresh();
        }

        SystemAuditLogger::record(
            'business_operating_modes_unlock_updated',
            'Superadmin updated specialized operating mode unlocks for ' . $business->name,
            $business->id,
            (int) $request->user()->id,
            [
                'before' => $before,
                'after' => $business->settings ?? [],
            ]
        );

        return redirect()
            ->route('superadmin.entities.show', ['businesses', $business->id, 'tab' => 'details'])
            ->with('success', 'Operating mode unlocks saved.');
    }
}
