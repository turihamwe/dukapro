<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use App\Models\Business;
use App\Models\EndOfDayReconciliation;
use App\Services\ReconciliationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReconciliationController extends Controller
{
    protected ReconciliationService $reconciliationService;

    public function __construct(ReconciliationService $reconciliationService)
    {
        $this->reconciliationService = $reconciliationService;
        $this->middleware('can:view-reconciliation-history')->only(['index', 'show', 'print']);
        $this->middleware('can:submit-reconciliation')->only(['create', 'store']);
    }

    public function index(Request $request)
    {
        $query = EndOfDayReconciliation::with('user')->latest('reconciliation_date');

        if (! Gate::allows('view-all-reconciliations')) {
            $query->where('user_id', $request->user()->id);
        }

        $reconciliations = $query->paginate(15);

        return view('reconciliation.index', compact('reconciliations'));
    }

    public function show(Request $request, Business $business, EndOfDayReconciliation $reconciliation)
    {
        $this->authorizeReconciliation($request, $reconciliation);

        $reconciliation->load('user', 'business');
        $report = $this->reconciliationService->buildReportDetails($reconciliation);
        $bossPhone = $this->resolveBossPhone($business);
        $whatsAppUrl = $this->reconciliationService->whatsAppShareUrl($reconciliation, $bossPhone);

        return view('reconciliation.show', compact('reconciliation', 'report', 'whatsAppUrl', 'bossPhone'));
    }

    public function print(Business $business, EndOfDayReconciliation $reconciliation)
    {
        $this->authorizeReconciliation(request(), $reconciliation);

        $reconciliation->load('user', 'business');
        $report = $this->reconciliationService->buildReportDetails($reconciliation);

        return view('reconciliation.print', compact('reconciliation', 'report'));
    }

    public function create(Request $request)
    {
        $date = $request->get('date', Carbon::today()->toDateString());
        $expected = $this->reconciliationService->calculateExpectedTotals(
            $request->user()->business_id,
            $request->user()->id,
            Carbon::parse($date)
        );

        return view('reconciliation.create', compact('expected', 'date'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'reconciliation_date' => 'required|date',
            'actual_cash' => 'required|numeric|min:0',
            'actual_mobile_money' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $reconciliation = $this->reconciliationService->submit($request->user(), $data);

        AuditLogger::record('reconciliation_submitted', $reconciliation, null, $reconciliation->toArray());

        return redirect()->to(tenant_route('tenant.reconciliation.show', ['reconciliation' => $reconciliation]))
            ->with('success', 'End-of-day reconciliation submitted. Cash variance: ' . format_money($reconciliation->cash_variance));
    }

    protected function authorizeReconciliation(Request $request, EndOfDayReconciliation $reconciliation): void
    {
        if (! Gate::allows('view-all-reconciliations') && (int) $reconciliation->user_id !== (int) $request->user()->id) {
            abort(403);
        }
    }

    protected function resolveBossPhone(Business $business): ?string
    {
        if ($business->phone) {
            return $business->phone;
        }

        $owner = $business->users()->where('role', 'owner')->first();

        return $owner->phone ?? null;
    }
}
