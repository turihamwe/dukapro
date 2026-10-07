<?php

namespace App\Http\Controllers;

use App\Services\BusinessFinancialStatementService;
use App\Services\ExpenseService;
use App\Support\ReportPeriodResolver;
use App\Support\ReportShareMessages;
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

        $statement = $statements->incomeStatement((int) $business->id, $start, $end);
        $shareMessage = ReportShareMessages::incomeStatement($business, $label, $statement);
        $shareSubject = 'Income statement — ' . $label . ' — ' . $business->name;

        return view('supplier-credit.financials.income-statement', [
            'business' => $business,
            'period' => $period,
            'label' => $label,
            'start' => $start,
            'end' => $end,
            'statement' => $statement,
            'expenseCategoryLabels' => $expenseService->categoriesForBusiness((int) $business->id),
            'shareWhatsAppUrl' => whatsapp_share_url(null, $shareMessage),
            'shareEmailUrl' => mailto_share_url($business->email, $shareSubject, $shareMessage),
        ]);
    }

    public function balanceSheet(Request $request, BusinessFinancialStatementService $statements)
    {
        $period = $request->input('period', 'monthly');
        [$start, $end, $label] = ReportPeriodResolver::resolve($period, $request);
        $business = $request->user()->business;

        $sheet = $statements->balanceSheet((int) $business->id, $end);
        $shareMessage = ReportShareMessages::balanceSheet($business, $label, $sheet);
        $shareSubject = 'Balance sheet — ' . $label . ' — ' . $business->name;

        return view('supplier-credit.financials.balance-sheet', [
            'business' => $business,
            'period' => $period,
            'label' => $label,
            'start' => $start,
            'end' => $end,
            'sheet' => $sheet,
            'shareWhatsAppUrl' => whatsapp_share_url(null, $shareMessage),
            'shareEmailUrl' => mailto_share_url($business->email, $shareSubject, $shareMessage),
        ]);
    }
}
