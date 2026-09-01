<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Helpers\SystemAuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Services\AffiliateRegistrationService;
use Illuminate\Http\Request;

class AffiliateActionController extends Controller
{
    protected AffiliateRegistrationService $registrationService;

    public function __construct(AffiliateRegistrationService $registrationService)
    {
        $this->registrationService = $registrationService;
    }

    public function approve(Request $request, Affiliate $affiliate)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $this->registrationService->approve($affiliate, $request->user());

        SystemAuditLogger::record(
            'affiliate_approved',
            'Approved affiliate #' . $affiliate->id . ' (' . $affiliate->email . ')',
            null,
            $request->user()->id
        );

        return back()->with('success', 'Affiliate approved and activated.');
    }

    public function reject(Request $request, Affiliate $affiliate)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $this->registrationService->reject($affiliate, $request->user());

        SystemAuditLogger::record(
            'affiliate_rejected',
            'Rejected affiliate #' . $affiliate->id . ' (' . $affiliate->email . ')',
            null,
            $request->user()->id
        );

        return back()->with('success', 'Affiliate application rejected.');
    }

    public function toggleActive(Request $request, Affiliate $affiliate)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $active = ! $affiliate->is_active;
        $this->registrationService->setActive($affiliate, $active);

        SystemAuditLogger::record(
            'affiliate_toggled',
            ($active ? 'Activated' : 'Deactivated') . ' affiliate #' . $affiliate->id,
            null,
            $request->user()->id
        );

        return back()->with('success', $active ? 'Affiliate activated.' : 'Affiliate deactivated.');
    }
}
