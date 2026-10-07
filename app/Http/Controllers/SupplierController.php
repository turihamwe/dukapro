<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use App\Models\Business;
use App\Models\Supplier;
use App\Services\SupplierCreditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SupplierController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:access-supplier-credit');
        $this->middleware('supplier.credit');
        $this->middleware('management.access');
    }

    public function index(Request $request)
    {
        $suppliers = Supplier::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->withCount(['creditPurchases as open_purchases_count' => function ($q) {
                $q->whereIn('status', ['open', 'partial']);
            }])
            ->get()
            ->map(function (Supplier $supplier) {
                $supplier->setAttribute('open_balance', $supplier->openBalance());

                return $supplier;
            });

        return view('supplier-credit.suppliers.index', [
            'suppliers' => $suppliers,
        ]);
    }

    public function store(Request $request, SupplierCreditService $supplierCreditService)
    {
        $businessId = (int) $request->user()->business_id;

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('suppliers', 'name')->where(fn ($q) => $q->where('business_id', $businessId)),
            ],
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string|max:2000',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        $openingBalance = round((float) ($data['opening_balance'] ?? 0), 2);

        $supplier = Supplier::create([
            'business_id' => $businessId,
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => true,
            'opening_balance' => $openingBalance,
        ]);

        if ($openingBalance > 0) {
            $supplierCreditService->ensureOpeningBalanceBill($request->user(), $supplier, $openingBalance);
        }

        AuditLogger::record('supplier_created', $supplier, null, $supplier->fresh()->toArray());

        $message = $openingBalance > 0
            ? 'Vendor added with an opening balance bill of ' . number_format($openingBalance, 2) . '.'
            : 'Vendor added.';

        return redirect()
            ->to(tenant_route('tenant.supplier-credit.vendors.index'))
            ->with('success', $message);
    }

    public function edit(Business $business, Supplier $supplier)
    {
        if ((int) $supplier->business_id !== (int) $business->id) {
            abort(404);
        }

        return view('supplier-credit.suppliers.edit', [
            'supplier' => $supplier->load('openingBalancePurchase'),
            'hasBills' => $supplier->creditPurchases()->exists(),
        ]);
    }

    public function update(Request $request, Business $business, Supplier $supplier, SupplierCreditService $supplierCreditService)
    {
        if ((int) $supplier->business_id !== (int) $business->id) {
            abort(404);
        }

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('suppliers', 'name')
                    ->where(fn ($q) => $q->where('business_id', $business->id))
                    ->ignore($supplier->id),
            ],
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string|max:2000',
            'is_active' => 'nullable|boolean',
        ];

        if (! $supplier->hasOpeningBalanceBill()) {
            $rules['opening_balance'] = 'nullable|numeric|min:0';
        }

        $data = $request->validate($rules);

        $openingBalance = round((float) ($data['opening_balance'] ?? $supplier->opening_balance ?? 0), 2);

        if ($supplier->hasOpeningBalanceBill() && $request->filled('opening_balance')) {
            $requested = round((float) $request->input('opening_balance'), 2);
            if (abs($requested - (float) $supplier->opening_balance) > 0.009) {
                throw ValidationException::withMessages([
                    'opening_balance' => 'Opening balance cannot be changed after the opening bill was created.',
                ]);
            }
        }

        $old = $supplier->toArray();

        $supplier->update([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'opening_balance' => $supplier->hasOpeningBalanceBill()
                ? $supplier->opening_balance
                : $openingBalance,
        ]);

        if (! $supplier->hasOpeningBalanceBill() && $openingBalance > 0) {
            $supplierCreditService->ensureOpeningBalanceBill($request->user(), $supplier->fresh(), $openingBalance);
        }

        AuditLogger::record('supplier_updated', $supplier, $old, $supplier->fresh()->toArray());

        return redirect()
            ->to(tenant_route('tenant.supplier-credit.vendors.index'))
            ->with('success', 'Vendor updated.');
    }

    public function destroy(Business $business, Supplier $supplier, SupplierCreditService $service, Request $request)
    {
        if ((int) $supplier->business_id !== (int) $business->id) {
            abort(404);
        }

        $service->softDeleteVendor($supplier, $request->user());

        return redirect()
            ->to(tenant_route('tenant.supplier-credit.vendors.index'))
            ->with('success', 'Vendor and linked bills/payments removed from active records. Stock levels were not changed.');
    }
}
