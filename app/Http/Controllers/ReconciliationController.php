<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use App\Models\Business;
use App\Models\EndOfDayReconciliation;
use App\Services\ReconciliationService;
use App\Support\ReconciliationVariance;
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
        $this->middleware('can:submit-reconciliation')->only(['create', 'store', 'edit', 'update']);
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
        $shortages = $reconciliation->shortages()->with('user')->get();
        $bossPhone = $this->resolveBossPhone($business);
        $whatsAppUrl = $this->reconciliationService->whatsAppShareUrl($reconciliation, $bossPhone);
        $canEdit = $this->canEditReconciliation($request, $reconciliation);

        return view('reconciliation.show', compact('reconciliation', 'report', 'whatsAppUrl', 'bossPhone', 'shortages', 'canEdit'));
    }

    public function print(Business $business, EndOfDayReconciliation $reconciliation)
    {
        $this->authorizeReconciliation(request(), $reconciliation);

        $reconciliation->load('user', 'business');
        $report = $this->reconciliationService->buildReportDetails($reconciliation);
        $shortages = $reconciliation->shortages()->with('user')->get();

        return view('reconciliation.print', compact('reconciliation', 'report', 'shortages'));
    }

    public function create(Request $request)
    {
        $date = $request->get('date', Carbon::today()->toDateString());
        $business = $request->user()->business;
        $expected = $this->reconciliationService->calculateExpectedTotals(
            $request->user()->business_id,
            $request->user()->id,
            Carbon::parse($date)
        );

        $waiterShift = null;
        $waiterBalances = collect();
        if ($business->usesPerWaiterShiftBalancing()) {
            $waiterShift = app(\App\Services\WaiterShiftService::class)->summarizeShift($business, Carbon::parse($date), $request->user());
            $waiterBalances = app(\App\Services\WaiterShiftService::class)->balancesForDate($business->id, Carbon::parse($date));
        }

        return view('reconciliation.create', compact('expected', 'date', 'waiterShift', 'waiterBalances', 'business'));
    }

    public function edit(Request $request, Business $business, EndOfDayReconciliation $reconciliation)
    {
        $this->authorizeEditableReconciliation($request, $reconciliation);

        $date = $reconciliation->reconciliation_date->toDateString();
        $expected = $this->reconciliationService->calculateExpectedTotals(
            $request->user()->business_id,
            $reconciliation->user_id,
            Carbon::parse($date)
        );

        $waiterShift = null;
        $waiterBalances = collect();
        if ($business->usesPerWaiterShiftBalancing()) {
            $waiterShift = app(\App\Services\WaiterShiftService::class)->summarizeShift($business, Carbon::parse($date), $reconciliation->user);
            $waiterBalances = app(\App\Services\WaiterShiftService::class)->balancesForDate($business->id, Carbon::parse($date));
        }

        return view('reconciliation.edit', compact('reconciliation', 'expected', 'date', 'waiterShift', 'waiterBalances', 'business'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedSubmission($request);
        $this->assertEditableDate(Carbon::parse($data['reconciliation_date']));

        $reconciliation = $request->user()->business->usesPerWaiterShiftBalancing()
            ? $this->reconciliationService->submitWithWaiterBalances($request->user(), $data)
            : $this->reconciliationService->submit($request->user(), $data);

        AuditLogger::record('reconciliation_submitted', $reconciliation, null, $reconciliation->toArray());

        return redirect()->to(tenant_route('tenant.reconciliation.show', ['reconciliation' => $reconciliation]))
            ->with('success', ReconciliationVariance::successMessage($reconciliation->missing_money ?? 0, $reconciliation->business));
    }

    public function update(Request $request, Business $business, EndOfDayReconciliation $reconciliation)
    {
        $this->authorizeEditableReconciliation($request, $reconciliation);

        $data = $this->validatedSubmission($request);
        $this->assertEditableDate(Carbon::parse($data['reconciliation_date']));

        $old = $reconciliation->toArray();

        $reconciliation = $reconciliation->user->business->usesPerWaiterShiftBalancing()
            ? $this->reconciliationService->submitWithWaiterBalances($reconciliation->user, $data)
            : $this->reconciliationService->submit($reconciliation->user, $data);

        AuditLogger::record('reconciliation_updated', $reconciliation, $old, $reconciliation->toArray());

        return redirect()->to(tenant_route('tenant.reconciliation.show', ['reconciliation' => $reconciliation]))
            ->with('success', ReconciliationVariance::successMessage($reconciliation->missing_money ?? 0, $reconciliation->business));
    }

    protected function validatedSubmission(Request $request): array
    {
        return $request->validate([
            'reconciliation_date' => 'required|date',
            'actual_cash' => 'required|numeric|min:0',
            'actual_mobile_money' => 'nullable|numeric|min:0',
            'actual_bank_other' => 'nullable|numeric|min:0',
            'extra_cash' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'bundle_waiter_balances' => 'nullable|boolean',
        ]);
    }

    protected function assertEditableDate(Carbon $date): void
    {
        abort_unless($date->isToday(), 403, 'Only today\'s reconciliation can be submitted or edited.');
    }

    protected function canEditReconciliation(Request $request, EndOfDayReconciliation $reconciliation): bool
    {
        if (! Gate::allows('submit-reconciliation')) {
            return false;
        }

        if ((int) $reconciliation->user_id !== (int) $request->user()->id) {
            return false;
        }

        return Carbon::parse($reconciliation->reconciliation_date)->isToday();
    }

    protected function authorizeEditableReconciliation(Request $request, EndOfDayReconciliation $reconciliation): void
    {
        abort_unless($this->canEditReconciliation($request, $reconciliation), 403);
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
