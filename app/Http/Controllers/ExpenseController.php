<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use App\Models\Business;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    protected ExpenseService $expenseService;

    public function __construct(ExpenseService $expenseService)
    {
        $this->expenseService = $expenseService;
        $this->authorizeResource(Expense::class, 'expense');
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $period = $request->input('period', 'daily');
        $business = $request->user()->business;

        [$start, $end, $label] = \App\Support\ReportPeriodResolver::resolve($period, $request);

        $query = Expense::with('user')
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->latest('expense_date')
            ->latest('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('category', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $expenses = $query->paginate(20)->appends($request->only(['search', 'period']));

        $totalQuery = Expense::query()
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()]);

        if ($search !== '') {
            $totalQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('category', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $periodTotal = (float) $totalQuery->sum('amount');
        $categories = $this->expenseService->categoriesForBusiness((int) $business->id);

        return view('expenses.index', compact('expenses', 'categories', 'search', 'period', 'label', 'periodTotal', 'business'));
    }

    public function create()
    {
        $businessId = (int) auth()->user()->business_id;

        return view('expenses.create', [
            'categories' => $this->expenseService->categoriesForBusiness($businessId),
            'quickCategoryUrl' => tenant_route('tenant.expenses.categories.quick-store'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'description' => 'nullable|string|max:1000',
            'payment_method' => 'nullable|in:cash,mobile_money,bank',
            'receipt_reference' => 'nullable|string|max:255',
        ]);

        $expense = $this->expenseService->create($request->user(), $data);

        AuditLogger::record('expense_created', $expense, null, $expense->toArray());

        if ($request->user()->usesCashierExperience()) {
            return redirect()
                ->to(tenant_route('tenant.expenses.create'))
                ->with('success', 'Expense recorded.');
        }

        return redirect()
            ->to(tenant_route('tenant.expenses.index', ['date' => $data['expense_date']]))
            ->with('success', 'Expense recorded.');
    }

    public function edit(Business $business, Expense $expense)
    {
        return view('expenses.edit', [
            'expense' => $expense,
            'categories' => $this->expenseService->categoriesForBusiness((int) $business->id),
        ]);
    }

    public function update(Request $request, Business $business, Expense $expense)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'description' => 'nullable|string|max:1000',
            'payment_method' => 'nullable|in:cash,mobile_money,bank',
            'receipt_reference' => 'nullable|string|max:255',
        ]);

        $this->expenseService->update($expense, $data);

        return redirect()
            ->to(tenant_route('tenant.expenses.index'))
            ->with('success', 'Expense updated.');
    }

    public function destroy(Business $business, Expense $expense)
    {
        $expense->delete();

        return back()->with('success', 'Expense removed.');
    }
}
