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

        $business = Business::query()->withTrashed()->findOrFail($businessId);

        $target = $this->resolveImpersonationUser($business);

        if (! $target) {
            return redirect()
                ->back()
                ->withErrors([
                    'impersonate' => 'No active owner or manager account found for this business. Create or activate an owner user first.',
                ]);
        }

        $superAdminId = (int) $request->user()->id;

        CashierMode::disable($request);
        Auth::login($target);
        $request->session()->regenerate();
        Impersonation::start($request, $superAdminId);

        SystemAuditLogger::record(
            'impersonation_started',
            'SuperAdmin impersonating ' . $target->role . ' of ' . $business->name,
            $business->id,
            $superAdminId
        );

        return redirect()
            ->to(tenant_route('tenant.dashboard'))
            ->with('info', 'Viewing as ' . $target->name . ' (' . $business->name . '). Use “Exit impersonation” when finished.');
    }

    protected function resolveImpersonationUser(Business $business): ?User
    {
        foreach ([UserRole::OWNER, UserRole::MANAGER] as $role) {
            $user = User::query()
                ->where('business_id', $business->id)
                ->where('role', $role)
                ->where('is_active', true)
                ->orderBy('id')
                ->first();

            if ($user) {
                return $user;
            }
        }

        return null;
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
            ->first();

        if (! $admin) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('warning', 'Impersonation session ended. Sign in again as SuperAdmin.');
        }

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
