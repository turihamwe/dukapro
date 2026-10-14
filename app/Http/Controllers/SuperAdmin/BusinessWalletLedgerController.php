<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Helpers\SystemAuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Support\WalletLedgerAdjustmentCompliance;
use Illuminate\Http\Request;

class BusinessWalletLedgerController extends Controller
{
    public function updateUnlock(Request $request, int $businessId)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $business = Business::query()->findOrFail($businessId);

        if (! WalletLedgerAdjustmentCompliance::globallyEnabled()) {
            return redirect()
                ->route('superadmin.entities.show', ['businesses', $business->id, 'tab' => 'details'])
                ->withErrors(['wallet_ledger' => 'Enable wallet ledger corrections in System Settings before unlocking them for individual businesses.']);
        }

        $request->validate([
            'wallet_ledger_adjustments_admin_unlocked' => 'nullable|boolean',
        ]);

        $unlocked = $request->boolean('wallet_ledger_adjustments_admin_unlocked');
        $before = $business->settings ?? [];

        WalletLedgerAdjustmentCompliance::setAdminUnlock($business, $unlocked);

        SystemAuditLogger::record(
            'business_wallet_ledger_unlock_updated',
            'Superadmin ' . ($unlocked ? 'unlocked' : 'locked') . ' wallet ledger corrections for ' . $business->name,
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
                ? 'Wallet ledger corrections enabled for this business.'
                : 'Wallet ledger corrections disabled for this business.');
    }
}
