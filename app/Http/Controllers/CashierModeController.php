<?php

namespace App\Http\Controllers;

use App\Support\CashierMode;
use Illuminate\Http\Request;

class CashierModeController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:switch-cashier-mode');
    }

    public function enable(Request $request)
    {
        CashierMode::enable($request);

        return redirect()
            ->to(tenant_route('tenant.pos.index'))
            ->with('success', 'You are now in Cashier Mode.');
    }

    public function disable(Request $request)
    {
        CashierMode::disable($request);

        return redirect()
            ->to(tenant_route('tenant.dashboard'))
            ->with('success', 'Returned to management dashboard.');
    }
}
