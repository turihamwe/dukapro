<?php

namespace App\Modules\Appointments\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AppointmentsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:access-appointments');
        $this->middleware('module:appointments');
        $this->middleware('management.access');
    }

    public function index(Request $request)
    {
        $business = $request->user()->business;

        return view('appointments.index', compact('business'));
    }
}
