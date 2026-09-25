<?php

namespace App\Http\Controllers;

use App\Models\HospitalityRoomBooking;
use App\Services\HospitalityBookingService;
use Illuminate\Http\Request;

class HospitalityBookingController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-hospitality-bookings');
        $this->middleware('hospitality');
        $this->middleware('management.access');
    }

    public function store(Request $request, HospitalityBookingService $service)
    {
        $business = $request->user()->business;

        $data = $request->validate([
            'product_id' => 'required|integer',
            'branch_id' => 'nullable|integer',
            'guest_name' => 'required|string|max:120',
            'guest_phone' => 'nullable|string|max:30',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'notes' => 'nullable|string|max:2000',
        ]);

        if (! empty($data['branch_id'])) {
            $request->validate([
                'branch_id' => 'exists:branches,id',
            ]);
        }

        $service->create($business, $data);

        return redirect()
            ->route('tenant.hospitality.index', ['business' => $business->slug])
            ->with('success', 'Room booking saved.');
    }

    public function checkIn(Request $request, HospitalityRoomBooking $booking, HospitalityBookingService $service)
    {
        $this->authorizeBooking($request, $booking);
        $service->checkIn($booking);

        return back()->with('success', 'Guest checked in.');
    }

    public function checkOut(Request $request, HospitalityRoomBooking $booking, HospitalityBookingService $service)
    {
        $this->authorizeBooking($request, $booking);
        $service->checkOut($booking);

        return back()->with('success', 'Guest checked out.');
    }

    public function cancel(Request $request, HospitalityRoomBooking $booking, HospitalityBookingService $service)
    {
        $this->authorizeBooking($request, $booking);
        $service->cancel($booking);

        return back()->with('success', 'Booking cancelled.');
    }

    protected function authorizeBooking(Request $request, HospitalityRoomBooking $booking): void
    {
        abort_unless(
            (int) $booking->business_id === (int) $request->user()->business_id,
            404
        );
    }
}
