<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use App\Models\Product;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Product::class, 'product');
    }

    public function index(Request $request)
    {
        $products = Product::orderBy('name')->paginate(20);

        return view('inventory.index', compact('products'));
    }

    public function create()
    {
        return view('inventory.create');
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'measurement_unit' => 'required|string|max:50',
            'stock_quantity' => 'required|numeric|min:0',
            'variant_attributes_json' => 'nullable|string',
        ];

        if ($request->user()->can('view-cost-prices')) {
            $rules['cost_price'] = 'nullable|numeric|min:0';
        }

        $data = $request->validate($rules);

        $data['variant_attributes'] = $this->parseVariantJson($data['variant_attributes_json'] ?? null);
        unset($data['variant_attributes_json']);

        if (! $request->user()->can('view-cost-prices')) {
            unset($data['cost_price']);
        }

        $product = Product::create(array_merge($data, ['is_active' => true]));

        AuditLogger::record('product_created', $product, null, $product->toArray());

        return redirect()->to(tenant_route('tenant.inventory.index'))->with('success', 'Product added.');
    }

    public function edit(Product $product)
    {
        return view('inventory.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'measurement_unit' => 'required|string|max:50',
            'stock_quantity' => 'required|numeric|min:0',
            'variant_attributes_json' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ];

        if ($request->user()->can('view-cost-prices')) {
            $rules['cost_price'] = 'nullable|numeric|min:0';
        }

        $data = $request->validate($rules);

        $data['variant_attributes'] = $this->parseVariantJson($data['variant_attributes_json'] ?? null);
        unset($data['variant_attributes_json']);
        $data['is_active'] = $request->boolean('is_active');

        if (! $request->user()->can('view-cost-prices')) {
            unset($data['cost_price']);
        }

        $old = $product->toArray();
        $product->update($data);

        AuditLogger::record('product_updated', $product, $old, $product->fresh()->toArray());

        return redirect()->to(tenant_route('tenant.inventory.index'))->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $old = $product->toArray();
        $product->delete();

        AuditLogger::record('product_deleted', $product, $old, null);

        return redirect()->to(tenant_route('tenant.inventory.index'))->with('success', 'Product removed.');
    }

    protected function parseVariantJson(?string $json): ?array
    {
        if (! $json) {
            return null;
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : null;
    }
}
