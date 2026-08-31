<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use App\Models\Business;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Product::class, 'product');
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        $query = Product::query()->orderBy('name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('sku', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('measurement_unit', 'like', '%' . $search . '%');
            });
        }

        $products = $query->paginate(20)->appends(['search' => $search]);

        return view('inventory.index', compact('products', 'search'));
    }

    public function create()
    {
        return view('inventory.create');
    }

    public function store(Request $request)
    {
        $businessId = (int) $request->user()->business_id;

        $rules = [
            'name' => 'required|string|max:255',
            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->where(fn ($query) => $query->where('business_id', $businessId)),
            ],
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'measurement_unit' => 'required|string|max:50',
            'stock_quantity' => 'required|numeric|min:0',
            'critical_threshold' => 'nullable|integer|min:0',
        ];

        if ($request->user()->can('view-cost-prices')) {
            $rules['cost_price'] = 'nullable|numeric|min:0';
        }

        $data = $request->validate($rules);

        if (! $request->user()->can('view-cost-prices')) {
            unset($data['cost_price']);
        }

        $product = Product::create(array_merge($data, ['is_active' => true]));

        AuditLogger::record('product_created', $product, null, $product->toArray());

        if ($request->boolean('add_another')) {
            return redirect()
                ->to(tenant_route('tenant.inventory.create'))
                ->with('success', 'Product added. Add the next item below.');
        }

        return redirect()->to(tenant_route('tenant.inventory.index'))->with('success', 'Product added.');
    }

    public function edit(Business $business, Product $product)
    {
        return view('inventory.edit', compact('product'));
    }

    public function update(Request $request, Business $business, Product $product)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'sku')
                    ->where(fn ($query) => $query->where('business_id', $business->id))
                    ->ignore($product->id),
            ],
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'measurement_unit' => 'required|string|max:50',
            'stock_quantity' => 'required|numeric|min:0',
            'critical_threshold' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ];

        if ($request->user()->can('view-cost-prices')) {
            $rules['cost_price'] = 'nullable|numeric|min:0';
        }

        $data = $request->validate($rules);
        $data['is_active'] = $request->boolean('is_active');

        if (! $request->user()->can('view-cost-prices')) {
            unset($data['cost_price']);
        }

        $old = $product->toArray();
        $product->update($data);

        AuditLogger::record('product_updated', $product, $old, $product->fresh()->toArray());

        return redirect()->to(tenant_route('tenant.inventory.index'))->with('success', 'Product updated.');
    }

    public function destroy(Business $business, Product $product)
    {
        $old = $product->toArray();
        $product->delete();

        AuditLogger::record('product_deleted', $product, $old, null);

        return redirect()->to(tenant_route('tenant.inventory.index'))->with('success', 'Product removed.');
    }
}
