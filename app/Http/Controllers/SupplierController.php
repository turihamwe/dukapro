<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

    public function store(Request $request)
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
        ]);

        Supplier::create(array_merge($data, [
            'business_id' => $businessId,
            'is_active' => true,
        ]));

        return redirect()
            ->to(tenant_route('tenant.supplier-credit.vendors.index'))
            ->with('success', 'Vendor added.');
    }

    public function edit(Business $business, Supplier $supplier)
    {
        if ((int) $supplier->business_id !== (int) $business->id) {
            abort(404);
        }

        return view('supplier-credit.suppliers.edit', [
            'supplier' => $supplier,
            'hasBills' => $supplier->creditPurchases()->exists(),
        ]);
    }

    public function update(Request $request, Business $business, Supplier $supplier)
    {
        if ((int) $supplier->business_id !== (int) $business->id) {
            abort(404);
        }

        $data = $request->validate([
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
        ]);

        $supplier->update([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->to(tenant_route('tenant.supplier-credit.vendors.index'))
            ->with('success', 'Vendor updated.');
    }

    public function destroy(Business $business, Supplier $supplier)
    {
        if ((int) $supplier->business_id !== (int) $business->id) {
            abort(404);
        }

        if ($supplier->creditPurchases()->exists()) {
            $supplier->update(['is_active' => false]);

            return redirect()
                ->to(tenant_route('tenant.supplier-credit.vendors.index'))
                ->with('success', 'Vendor deactivated. Bill history is kept; you can reactivate under Edit.');
        }

        $supplier->delete();

        return redirect()
            ->to(tenant_route('tenant.supplier-credit.vendors.index'))
            ->with('success', 'Vendor removed.');
    }
}
