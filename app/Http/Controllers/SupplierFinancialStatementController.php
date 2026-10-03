<?php

namespace App\Http\Controllers;

use App\Services\BusinessFinancialStatementService;
use App\Services\ExpenseService;
use App\Support\ReportPeriodResolver;
use Illuminate\Http\Request;

class SupplierFinancialStatementController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:access-supplier-credit');
        $this->middleware('supplier.credit');
        $this->middleware('management.access');
    }

    public function incomeStatement(Request $request, BusinessFinancialStatementService $statements, ExpenseService $expenseService)
    {
        $period = $request->input('period', 'monthly');
        [$start, $end, $label] = ReportPeriodResolver::resolve($period, $request);
        $business = $request->user()->business;

        return view('supplier-credit.financials.income-statement', [
            'business' => $business,
            'period' => $period,
            'label' => $label,
            'start' => $start,
            'end' => $end,
            'statement' => $statements->incomeStatement((int) $business->id, $start, $end),
            'expenseCategoryLabels' => $expenseService->categoriesForBusiness((int) $business->id),
        ]);
    }

    public function balanceSheet(Request $request, BusinessFinancialStatementService $statements)
    {
        $period = $request->input('period', 'monthly');
        [$start, $end, $label] = ReportPeriodResolver::resolve($period, $request);
        $business = $request->user()->business;

        return view('supplier-credit.financials.balance-sheet', [
            'business' => $business,
            'period' => $period,
            'label' => $label,
            'start' => $start,
            'end' => $end,
            'sheet' => $statements->balanceSheet((int) $business->id, $end),
        ]);
    }
}
