<?php

namespace App\Http\Controllers;

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

    public function create(Request $request)
    {
        $this->authorize('submit-reconciliation');

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
        $this->authorize('submit-reconciliation');

        $data = $request->validate([
            'reconciliation_date' => 'required|date',
            'actual_cash' => 'required|numeric|min:0',
            'actual_mobile_money' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $reconciliation = $this->reconciliationService->submit($request->user(), $data);

        return redirect()->to(tenant_route('tenant.reconciliation.index'))
            ->with('success', 'End-of-day reconciliation submitted. Cash variance: ' . format_money($reconciliation->cash_variance));
    }
}
