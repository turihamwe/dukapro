<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Customer;
use App\Services\CustomerCreditService;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerCreditCustomerController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:access-customer-credit');
        $this->middleware('customer.credit');
        $this->middleware('management.access');
    }

    public function index(Request $request)
    {
        $customers = Customer::query()
            ->where('is_credit_customer', true)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->withCount('debtEntries')
            ->paginate(25);

        return view('customer-credit.customers.index', [
            'customers' => $customers,
        ]);
    }

    public function show(Business $business, Customer $customer)
    {
        if ((int) $customer->business_id !== (int) $business->id) {
            abort(404);
        }

        $entries = $customer->debtEntries()->with('user', 'sale')->latest()->paginate(25);
        $creditSales = $customer->sales()->where('is_credit_sale', true)->latest()->limit(10)->get();

        return view('customer-credit.customers.show', [
            'customer' => $customer->load('openingBalanceEntry'),
            'entries' => $entries,
            'creditSales' => $creditSales,
        ]);
    }

    public function store(Request $request, CustomerService $customerService, CustomerCreditService $creditService)
    {
        $businessId = (int) $request->user()->business_id;

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms_days' => 'nullable|integer|min:1|max:365',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        $normalizedPhone = $customerService->preparePhoneForStorage($data['phone'] ?? null);

        if ($normalizedPhone) {
            $existing = $customerService->findByPhone($businessId, $normalizedPhone);

            if ($existing) {
                return redirect()
                    ->to(tenant_route('tenant.customer-credit.customers.show', ['customer' => $existing]))
                    ->with('info', 'A customer with this phone number already exists.');
            }
        }

        $openingBalance = round((float) ($data['opening_balance'] ?? 0), 2);

        $customer = Customer::create([
            'business_id' => $businessId,
            'name' => $data['name'],
            'company_name' => $data['company_name'] ?? null,
            'phone' => $normalizedPhone,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'credit_limit' => (float) ($data['credit_limit'] ?? 0),
            'payment_terms_days' => (int) ($data['payment_terms_days'] ?? 30),
            'is_credit_customer' => true,
            'is_active' => true,
            'opening_balance' => $openingBalance,
        ]);

        if ($openingBalance > 0) {
            $creditService->ensureOpeningBalanceEntry($request->user(), $customer, $openingBalance);
        }

        $message = $openingBalance > 0
            ? 'Customer added with opening balance of ' . number_format($openingBalance, 2) . '.'
            : 'Customer added.';

        return redirect()
            ->to(tenant_route('tenant.customer-credit.customers.index'))
            ->with('success', $message);
    }

    public function edit(Business $business, Customer $customer)
    {
        if ((int) $customer->business_id !== (int) $business->id) {
            abort(404);
        }

        return view('customer-credit.customers.edit', [
            'customer' => $customer->load('openingBalanceEntry'),
        ]);
    }

    public function update(Request $request, Business $business, Customer $customer, CustomerCreditService $creditService, CustomerService $customerService)
    {
        if ((int) $customer->business_id !== (int) $business->id) {
            abort(404);
        }

        $rules = [
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms_days' => 'nullable|integer|min:1|max:365',
            'is_active' => 'nullable|boolean',
        ];

        if (! $customer->hasOpeningBalanceEntry()) {
            $rules['opening_balance'] = 'nullable|numeric|min:0';
        }

        $data = $request->validate($rules);

        $normalizedPhone = $customerService->preparePhoneForStorage($data['phone'] ?? null);

        if ($normalizedPhone) {
            $existing = $customerService->findByPhone((int) $business->id, $normalizedPhone);

            if ($existing && (int) $existing->id !== (int) $customer->id) {
                return back()
                    ->withInput()
                    ->withErrors(['phone' => 'Another customer already uses this phone number.']);
            }
        }

        $openingBalance = round((float) ($data['opening_balance'] ?? $customer->opening_balance ?? 0), 2);

        if ($customer->hasOpeningBalanceEntry() && $request->filled('opening_balance')) {
            $requested = round((float) $request->input('opening_balance'), 2);
            if (abs($requested - (float) $customer->opening_balance) > 0.009) {
                throw ValidationException::withMessages([
                    'opening_balance' => 'Opening balance cannot be changed after the opening entry was created.',
                ]);
            }
        }

        $customer->update([
            'name' => $data['name'],
            'company_name' => $data['company_name'] ?? null,
            'phone' => $normalizedPhone,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'credit_limit' => (float) ($data['credit_limit'] ?? 0),
            'payment_terms_days' => (int) ($data['payment_terms_days'] ?? 30),
            'is_credit_customer' => true,
            'is_active' => $request->boolean('is_active', true),
            'opening_balance' => $customer->hasOpeningBalanceEntry()
                ? $customer->opening_balance
                : $openingBalance,
        ]);

        if (! $customer->hasOpeningBalanceEntry() && $openingBalance > 0) {
            $creditService->ensureOpeningBalanceEntry($request->user(), $customer->fresh(), $openingBalance);
        }

        return redirect()
            ->to(tenant_route('tenant.customer-credit.customers.index'))
            ->with('success', 'Customer updated.');
    }

    public function destroy(Business $business, Customer $customer, CustomerCreditService $service, Request $request)
    {
        if ((int) $customer->business_id !== (int) $business->id) {
            abort(404);
        }

        $service->softDeleteCustomer($customer, $request->user());

        return redirect()
            ->to(tenant_route('tenant.customer-credit.customers.index'))
            ->with('success', 'Customer and linked invoices/payments removed from active records. Stock levels were not changed.');
    }
}
