<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use App\Models\Business;
use App\Models\ReconciliationShortage;
use App\Services\ReconciliationShortageService;
use Illuminate\Http\Request;

class ReconciliationShortageController extends Controller
{
    protected ReconciliationShortageService $shortageService;

    public function __construct(ReconciliationShortageService $shortageService)
    {
        $this->shortageService = $shortageService;
        $this->middleware('can:view-reconciliation-shortages');
    }

    public function index(Request $request)
    {
        $status = $request->get('status', 'pending');
        $businessId = $request->user()->business_id;
        $shortages = $this->shortageService->listForBusiness(
            $businessId,
            $status === 'all' ? null : $status
        );

        $pendingTotal = $this->shortageService->listForBusiness($businessId, 'pending')
            ->sum(fn (ReconciliationShortage $shortage) => $shortage->outstandingAmount());

        return view('reconciliation-shortages.index', compact('shortages', 'status', 'pendingTotal'));
    }

    public function settle(Request $request, Business $business, ReconciliationShortage $shortage)
    {
        $this->authorize('settle-reconciliation-shortages');

        $data = $request->validate([
            'amount_settled' => 'nullable|numeric|min:0.01',
            'settlement_notes' => 'nullable|string|max:1000',
        ]);

        $shortage = $this->shortageService->settle($request->user(), $shortage, $data);

        AuditLogger::record('reconciliation_shortage_settled', $shortage, null, $shortage->toArray());

        return redirect()
            ->to(tenant_route('tenant.reconciliation-shortages.index'))
            ->with('success', $shortage->user->name . ' cleared for ' . format_money($shortage->amount, $business) . '.');
    }

    public function waive(Request $request, Business $business, ReconciliationShortage $shortage)
    {
        $this->authorize('settle-reconciliation-shortages');

        $data = $request->validate([
            'settlement_notes' => 'nullable|string|max:1000',
        ]);

        $shortage = $this->shortageService->waive($request->user(), $shortage, $data['settlement_notes'] ?? null);

        AuditLogger::record('reconciliation_shortage_waived', $shortage, null, $shortage->toArray());

        return redirect()
            ->to(tenant_route('tenant.reconciliation-shortages.index'))
            ->with('success', 'Shortage waived for ' . $shortage->user->name . '.');
    }
}
