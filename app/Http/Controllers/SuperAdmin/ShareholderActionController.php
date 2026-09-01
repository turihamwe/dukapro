<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Helpers\SystemAuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Shareholder;
use App\Services\ShareholderEarningsService;
use App\Services\ShareholderRegistrationService;
use Illuminate\Http\Request;

class ShareholderActionController extends Controller
{
    protected ShareholderRegistrationService $registrationService;

    protected ShareholderEarningsService $earningsService;

    public function __construct(
        ShareholderRegistrationService $registrationService,
        ShareholderEarningsService $earningsService
    ) {
        $this->registrationService = $registrationService;
        $this->earningsService = $earningsService;
    }

    public function approve(Request $request, Shareholder $shareholder)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $this->registrationService->approve($shareholder, $request->user());

        SystemAuditLogger::record(
            'shareholder_approved',
            'Approved shareholder #' . $shareholder->id . ' (' . $shareholder->email . ')',
            null,
            $request->user()->id
        );

        return back()->with('success', 'Shareholder approved and shares allocated.');
    }

    public function reject(Request $request, Shareholder $shareholder)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $this->registrationService->reject($shareholder, $request->user());

        SystemAuditLogger::record(
            'shareholder_rejected',
            'Rejected shareholder #' . $shareholder->id . ' (' . $shareholder->email . ')',
            null,
            $request->user()->id
        );

        return back()->with('success', 'Shareholder application rejected.');
    }

    public function toggleActive(Request $request, Shareholder $shareholder)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $active = ! $shareholder->is_active;
        $this->registrationService->setActive($shareholder, $active);

        SystemAuditLogger::record(
            'shareholder_toggled',
            ($active ? 'Activated' : 'Deactivated') . ' shareholder #' . $shareholder->id,
            null,
            $request->user()->id
        );

        return back()->with('success', $active ? 'Shareholder activated.' : 'Shareholder deactivated.');
    }

    public function recordEarning(Request $request, Shareholder $shareholder)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:100',
        ]);

        $this->earningsService->record(
            $shareholder,
            (float) $data['amount'],
            $request->user(),
            $data['description'] ?? null,
            $data['reference'] ?? null
        );

        SystemAuditLogger::record(
            'shareholder_earning_recorded',
            'Recorded UGX ' . number_format($data['amount'], 0) . ' for shareholder #' . $shareholder->id,
            null,
            $request->user()->id
        );

        return back()->with('success', 'Earnings recorded successfully.');
    }
}
