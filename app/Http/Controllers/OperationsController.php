<?php

namespace App\Http\Controllers;

use App\Services\LowStockAlertService;
use Illuminate\Http\Request;

class OperationsController extends Controller
{
    protected LowStockAlertService $lowStockAlertService;

    public function __construct(LowStockAlertService $lowStockAlertService)
    {
        $this->lowStockAlertService = $lowStockAlertService;
    }

    public function index(Request $request)
    {
        $user = $request->user();

        abort_unless(
            $user->can('view-inventory')
                || $user->can('record-expenses')
                || $user->can('log-damages'),
            403
        );

        $lowStockItems = $this->lowStockAlertService->lowStockProducts($user->business, $user);

        return view('operations.index', compact('lowStockItems'));
    }
}
