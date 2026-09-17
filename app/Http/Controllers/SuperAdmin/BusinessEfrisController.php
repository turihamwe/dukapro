<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Helpers\SystemAuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\EfrisSetting;
use App\Support\EfrisCompliance;
use Illuminate\Http\Request;

class BusinessEfrisController extends Controller
{
    public function updateUnlock(Request $request, int $businessId)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $business = Business::query()->findOrFail($businessId);

        if (! EfrisCompliance::globallyEnabled()) {
            return redirect()
                ->route('superadmin.entities.show', ['businesses', $business->id, 'tab' => 'details'])
                ->withErrors(['efris' => 'Enable "Use EFRIS" in System Settings before unlocking EFRIS for individual businesses.']);
        }

        $request->validate([
            'efris_admin_unlocked' => 'nullable|boolean',
        ]);

        $unlocked = $request->boolean('efris_admin_unlocked');

        $settings = EfrisSetting::query()->firstOrCreate(
            ['business_id' => $business->id],
            [
                'efris_enabled' => false,
                'efris_admin_unlocked' => false,
            ]
        );

        $before = [
            'efris_admin_unlocked' => (bool) $settings->efris_admin_unlocked,
            'efris_enabled' => (bool) $settings->efris_enabled,
        ];

        $settings->efris_admin_unlocked = $unlocked;

        if (! $unlocked) {
            $settings->efris_enabled = false;
        }

        $settings->save();

        SystemAuditLogger::record(
            'business_efris_unlock_updated',
            'Superadmin ' . ($unlocked ? 'unlocked' : 'locked') . ' EFRIS for ' . $business->name,
            $business->id,
            (int) $request->user()->id,
            [
                'before' => $before,
                'after' => [
                    'efris_admin_unlocked' => $unlocked,
                    'efris_enabled' => (bool) $settings->efris_enabled,
                ],
            ]
        );

        return redirect()
            ->route('superadmin.entities.show', ['businesses', $business->id, 'tab' => 'details'])
            ->with('success', $unlocked
                ? 'EFRIS compliance unlocked for this business.'
                : 'EFRIS compliance locked. Fiscal receipt submission is disabled.');
    }
}
