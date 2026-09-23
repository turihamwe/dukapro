<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Helpers\SystemAuditLogger;
use App\Http\Controllers\Controller;
use App\Enums\AffiliateStatus;
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
        abort_unless($request->user()->can('approve-affiliates'), 403);

        $this->registrationService->approve($affiliate, $request->user());

        SystemAuditLogger::record(
            'affiliate_approved',
            'Approved affiliate #' . $affiliate->id . ' (' . $affiliate->email . ')',
            null,
            $request->user()->id
        );

        return back()->with('success', 'Affiliate approved and activated.');
    }

    public function bulkApprove(Request $request)
    {
        abort_unless($request->user()->can('approve-affiliates'), 403);

        $data = $request->validate([
            'affiliate_ids' => 'required|array|min:1|max:100',
            'affiliate_ids.*' => 'integer|distinct|exists:affiliates,id',
        ]);

        $ids = array_values(array_unique(array_map('intval', $data['affiliate_ids'])));

        $affiliates = Affiliate::query()
            ->whereIn('id', $ids)
            ->where('status', AffiliateStatus::PENDING)
            ->orderBy('id')
            ->get();

        if ($affiliates->isEmpty()) {
            return back()->with('error', 'None of the selected affiliates are pending approval.');
        }

        foreach ($affiliates as $affiliate) {
            $this->registrationService->approve($affiliate, $request->user());

            SystemAuditLogger::record(
                'affiliate_approved',
                'Approved affiliate #' . $affiliate->id . ' (' . $affiliate->email . ')',
                null,
                $request->user()->id
            );
        }

        $approvedCount = $affiliates->count();
        $skippedCount = count($ids) - $approvedCount;

        $message = $approvedCount === 1
            ? '1 affiliate approved and activated.'
            : $approvedCount . ' affiliates approved and activated.';

        if ($skippedCount > 0) {
            $message .= ' ' . $skippedCount . ' selected '
                . ($skippedCount === 1 ? 'row was' : 'rows were')
                . ' skipped (not pending).';
        }

        return back()->with('success', $message);
    }

    public function reject(Request $request, Affiliate $affiliate)
    {
        abort_unless($request->user()->can('approve-affiliates'), 403);

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
