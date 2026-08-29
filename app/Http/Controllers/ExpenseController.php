<?php

namespace App\Http\Controllers;

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
        $date = $request->input('date');

        $query = Expense::with('user')->latest('expense_date')->latest('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('category', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if ($date) {
            $query->whereDate('expense_date', $date);
        }

        $expenses = $query->paginate(20)->appends($request->only(['search', 'date']));
        $categories = ExpenseService::CATEGORIES;
        $todayTotal = $this->expenseService->totalForDate($request->user()->business, now());

        return view('expenses.index', compact('expenses', 'categories', 'search', 'date', 'todayTotal'));
    }

    public function create()
    {
        return view('expenses.create', [
            'categories' => ExpenseService::CATEGORIES,
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

        $this->expenseService->create($request->user(), $data);

        if ($request->user()->isCashier()) {
            return redirect()
                ->to(tenant_route('tenant.pos.index'))
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
            'categories' => ExpenseService::CATEGORIES,
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
