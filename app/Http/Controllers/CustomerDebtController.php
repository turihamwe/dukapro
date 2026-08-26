<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
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
        $this->middleware('can:view-customers')->only(['index', 'show', 'edit']);
        $this->middleware('can:manage-debts')->only(['create', 'store', 'update', 'recordPayment', 'destroy']);
    }

    public function index(Request $request)
    {
        $filter = $request->input('filter', 'all');

        $query = Customer::withCount('debtEntries')->orderBy('name');

        if ($filter === 'credit') {
            $query->where('is_credit_customer', true);
        }

        $customers = $query->paginate(20)->withQueryString();

        return view('contacts.index', compact('customers', 'filter'));
    }

    public function create()
    {
        return view('contacts.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'credit_limit' => 'nullable|numeric|min:0',
            'is_credit_customer' => 'nullable|boolean',
        ]);

        $this->authorize('create', Customer::class);

        $isCredit = $request->boolean('is_credit_customer');

        $contact = Customer::create([
            'name' => $data['name'],
            'company_name' => $data['company_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'credit_limit' => $isCredit ? ($data['credit_limit'] ?? 0) : 0,
            'is_credit_customer' => $isCredit,
            'is_active' => true,
        ]);

        AuditLogger::record('contact_created', $contact, null, $contact->toArray());

        return redirect()->to(tenant_route('tenant.contacts.index'))->with('success', 'Contact added.');
    }

    public function show(Business $business, Customer $customer)
    {
        $this->authorize('view', $customer);

        $entries = $customer->debtEntries()->with('user', 'sale')->latest()->paginate(25);

        return view('contacts.show', compact('customer', 'entries'));
    }

    public function edit(Business $business, Customer $customer)
    {
        $this->authorize('update', $customer);

        return view('contacts.edit', compact('customer'));
    }

    public function update(Request $request, Business $business, Customer $customer)
    {
        $this->authorize('update', $customer);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'credit_limit' => 'nullable|numeric|min:0',
            'is_credit_customer' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $isCredit = $request->boolean('is_credit_customer');
        $old = $customer->toArray();

        $customer->update([
            'name' => $data['name'],
            'company_name' => $data['company_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'credit_limit' => $isCredit ? ($data['credit_limit'] ?? 0) : 0,
            'is_credit_customer' => $isCredit,
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditLogger::record('contact_updated', $customer, $old, $customer->fresh()->toArray());

        return redirect()->to(tenant_route('tenant.contacts.show', ['customer' => $customer]))->with('success', 'Contact updated.');
    }

    public function destroy(Business $business, Customer $customer)
    {
        $this->authorize('delete', $customer);

        $old = $customer->toArray();
        $customer->delete();

        AuditLogger::record('contact_deleted', $customer, $old, null);

        return redirect()->to(tenant_route('tenant.contacts.index'))->with('success', 'Contact removed.');
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
