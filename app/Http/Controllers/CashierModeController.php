<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use App\Helpers\SystemAuditLogger;
use App\Support\CashierMode;
use Illuminate\Http\Request;

class CashierModeController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:switch-cashier-mode');
    }

    public function enable(Request $request)
    {
        CashierMode::enable($request);

        $user = $request->user();

        if ($user->isOwner()) {
            AuditLogger::record('cashier_mode_enabled', null, null, [
                'mode' => 'cashier',
                'performer_label' => 'Owner acting as cashier',
            ]);

            SystemAuditLogger::record(
                'cashier_mode_enabled',
                'Owner ' . $user->email . ' entered cashier mode',
                $user->business_id,
                $user->id,
                ['acting_as_cashier' => true, 'performed_by_role' => 'owner']
            );
        }

        return redirect()
            ->to(tenant_route('tenant.pos.index'))
            ->with('success', 'You are now in Cashier Mode.');
    }

    public function disable(Request $request)
    {
        CashierMode::disable($request);

        $user = $request->user();

        if ($user->isOwner()) {
            AuditLogger::record('cashier_mode_disabled', null, null, [
                'mode' => 'management',
                'performer_label' => 'Owner returned to management',
            ]);

            SystemAuditLogger::record(
                'cashier_mode_disabled',
                'Owner ' . $user->email . ' exited cashier mode',
                $user->business_id,
                $user->id,
                ['acting_as_cashier' => false, 'performed_by_role' => 'owner']
            );
        }

        return redirect()
            ->to(tenant_route('tenant.dashboard'))
            ->with('success', 'Returned to management dashboard.');
    }
}
