<?php

namespace App\Http\Controllers;

use App\Services\HospitalityBookingService;
use App\Support\BusinessModeCompliance;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HospitalityController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:access-hospitality');
        $this->middleware('hospitality');
        $this->middleware('management.access');
    }

    public function index(Request $request, HospitalityBookingService $bookings)
    {
        abort_unless(BusinessModeCompliance::hospitalityModeActive($request->user()->business), 404);

        $business = $request->user()->business;

        $from = $request->filled('from') ? Carbon::parse($request->input('from')) : null;
        $to = $request->filled('to') ? Carbon::parse($request->input('to')) : null;

        return view('hospitality.index', [
            'rooms' => $bookings->rentableRooms($business),
            'ledger' => $bookings->ledger($business, $from, $to),
            'filterFrom' => $from?->toDateString(),
            'filterTo' => $to?->toDateString(),
            'branches' => $business->branches()->orderBy('name')->get(),
        ]);
    }
}
