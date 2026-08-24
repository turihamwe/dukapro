<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Customer;
use App\Services\DebtLedgerService;
use Illuminate\Http\Request;

class CustomerDebtController extends Controller
{
    protected DebtLedgerService $debtLedgerService;

    public function __construct(DebtLedgerService $debtLedgerService)
    {
        $this->debtLedgerService = $debtLedgerService;
        $this->middleware('can:manage-debts');
        $this->authorizeResource(Customer::class, 'customer');
    }

    public function index(Request $request)
    {
        $customers = Customer::withCount('debtEntries')
            ->orderByDesc('outstanding_balance')
            ->paginate(20);

        return view('debts.index', compact('customers'));
    }

    public function create()
    {
        return view('debts.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'credit_limit' => 'required|numeric|min:0',
        ]);

        Customer::create(array_merge($data, ['is_active' => true]));

        return redirect()->to(tenant_route('tenant.debts.index'))->with('success', 'Customer added.');
    }

    public function show(Business $business, Customer $customer)
    {
        $entries = $customer->debtEntries()->with('user', 'sale')->latest()->paginate(25);

        return view('debts.show', compact('customer', 'entries'));
    }

    public function recordPayment(Request $request, Business $business, Customer $customer)
    {
        $this->authorize('view', $customer);

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        $this->debtLedgerService->recordPayment(
            $customer,
            $data['amount'],
            $request->user(),
            $data['description'] ?? null
        );

        return back()->with('success', 'Payment recorded.');
    }
}
