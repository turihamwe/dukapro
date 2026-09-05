<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Sale;
use App\Models\User;
use App\Services\WaiterShiftService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WaiterShiftController extends Controller
{
    protected WaiterShiftService $waiterShiftService;

    public function __construct(WaiterShiftService $waiterShiftService)
    {
        $this->waiterShiftService = $waiterShiftService;
        $this->middleware('can:access-pos');
    }

    public function index(Request $request)
    {
        $business = $request->user()->business;
        abort_unless($business->usesShiftBalancing(), 404);

        $date = Carbon::parse($request->get('date', Carbon::today()->toDateString()));
        $shift = $this->waiterShiftService->summarizeShift($business, $date, $request->user());

        return view('waiter-shift.index', compact('shift', 'date', 'business'));
    }

    public function show(Request $request, Business $business, User $waiter)
    {
        abort_unless($business->usesShiftBalancing(), 404);
        abort_unless((int) $waiter->business_id === (int) $business->id, 404);
        abort_unless($this->waiterShiftService->isFloorStaffMember($waiter), 404);

        $date = Carbon::parse($request->get('date', Carbon::today()->toDateString()));
        $summary = $this->waiterShiftService->calculateWaiterSummary($business->id, $waiter->id, $date);

        if ($summary['expected_mobile_unspecified'] > 0) {
            $summary['expected_mobile_mtn'] += $summary['expected_mobile_unspecified'];
        }

        $balance = \App\Models\ShiftWaiterBalance::query()
            ->where('business_id', $business->id)
            ->where('shift_date', $date->toDateString())
            ->where('waiter_user_id', $waiter->id)
            ->first();

        return view('waiter-shift.show', compact('waiter', 'summary', 'date', 'business', 'balance'));
    }

    public function balanceAll(Request $request)
    {
        $business = $request->user()->business;
        abort_unless($business->usesShiftBalancing(), 404);

        $data = $request->validate([
            'shift_date' => 'required|date',
            'waiters' => 'required|array|min:1',
            'waiters.*.waiter_user_id' => 'required|exists:users,id',
            'waiters.*.actual_cash' => 'nullable|numeric|min:0',
            'waiters.*.actual_mobile_airtel' => 'nullable|numeric|min:0',
            'waiters.*.actual_mobile_mtn' => 'nullable|numeric|min:0',
            'waiters.*.actual_bank_other' => 'nullable|numeric|min:0',
            'waiters.*.actual_credit_collected' => 'nullable|numeric|min:0',
            'waiters.*.notes' => 'nullable|string|max:1000',
        ]);

        $date = Carbon::parse($data['shift_date']);
        $this->waiterShiftService->balanceAllWaiters($request->user(), $date, $data['waiters']);

        return redirect()
            ->to(tenant_route('tenant.waiter-shift.index', ['date' => $date->toDateString()]))
            ->with('success', 'All waiter submissions recorded. You can now close shift with EOD.');
    }

    public function settleCredit(Request $request, Business $business, Sale $sale)
    {
        abort_unless($business->usesShiftBalancing(), 404);
        abort_unless((int) $sale->business_id === (int) $business->id, 404);

        $data = $request->validate([
            'settlement_method' => 'required|in:cash,mobile_money,bank',
            'notes' => 'nullable|string|max:500',
        ]);

        $this->waiterShiftService->settleCreditSale($request->user(), $sale, $data);

        $waiterId = $sale->waiter_id;
        $date = optional($sale->completed_at)->toDateString() ?? Carbon::today()->toDateString();

        return redirect()
            ->to(tenant_route('tenant.waiter-shift.show', [
                'waiter' => $waiterId,
                'date' => $date,
            ]))
            ->with('success', 'Credit tab marked as settled.');
    }
}
