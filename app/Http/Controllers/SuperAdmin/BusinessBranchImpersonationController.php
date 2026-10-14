<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Helpers\SystemAuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Support\BranchImpersonationCompliance;
use Illuminate\Http\Request;

class BusinessBranchImpersonationController extends Controller
{
    public function updateUnlock(Request $request, int $businessId)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $business = Business::query()->findOrFail($businessId);

        if (! BranchImpersonationCompliance::globallyEnabled()) {
            return redirect()
                ->route('superadmin.entities.show', ['businesses', $business->id, 'tab' => 'details'])
                ->withErrors(['branch_impersonation' => 'Enable branch context switching in System Settings before unlocking it for individual businesses.']);
        }

        $request->validate([
            'branch_impersonation_admin_unlocked' => 'nullable|boolean',
        ]);

        $unlocked = $request->boolean('branch_impersonation_admin_unlocked');
        $before = $business->settings ?? [];

        BranchImpersonationCompliance::setAdminUnlock($business, $unlocked);

        SystemAuditLogger::record(
            'business_branch_impersonation_unlock_updated',
            'Superadmin ' . ($unlocked ? 'unlocked' : 'locked') . ' branch context switching for ' . $business->name,
            $business->id,
            (int) $request->user()->id,
            [
                'before' => $before,
                'after' => $business->fresh()->settings ?? [],
            ]
        );

        return redirect()
            ->route('superadmin.entities.show', ['businesses', $business->id, 'tab' => 'details'])
            ->with('success', $unlocked
                ? 'Branch context switching enabled for this business.'
                : 'Branch context switching disabled for this business.');
    }
}
