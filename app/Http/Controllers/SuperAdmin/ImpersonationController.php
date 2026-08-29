<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\UserRole;
use App\Helpers\SystemAuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\User;
use App\Support\CashierMode;
use App\Support\Impersonation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function start(Request $request, int $businessId)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $business = Business::query()->findOrFail($businessId);

        $owner = User::query()
            ->where('business_id', $business->id)
            ->where('role', UserRole::OWNER)
            ->where('is_active', true)
            ->first();

        abort_unless($owner, 404, 'No active owner account found for this business.');

        Impersonation::start($request, $request->user()->id);
        CashierMode::disable($request);

        Auth::login($owner);
        $request->session()->regenerate();

        SystemAuditLogger::record(
            'impersonation_started',
            'SuperAdmin impersonating owner of ' . $business->name,
            $business->id,
            (int) Impersonation::impersonatorId($request)
        );

        return redirect()
            ->route('tenant.dashboard', ['business' => $business->slug])
            ->with('info', 'Viewing as owner of ' . $business->name . '. Use “Exit impersonation” when finished.');
    }

    public function leave(Request $request)
    {
        $impersonatorId = Impersonation::impersonatorId($request);
        abort_unless($impersonatorId, 403);

        $businessId = optional($request->user())->business_id;

        Impersonation::stop($request);
        CashierMode::disable($request);

        $admin = User::query()
            ->where('id', $impersonatorId)
            ->where('is_super_admin', true)
            ->firstOrFail();

        Auth::login($admin);
        $request->session()->regenerate();

        SystemAuditLogger::record(
            'impersonation_ended',
            'SuperAdmin stopped impersonating business #' . ($businessId ?? 'unknown'),
            $businessId,
            $admin->id
        );

        return redirect()
            ->route('superadmin.dashboard')
            ->with('success', 'Returned to SuperAdmin dashboard.');
    }
}
