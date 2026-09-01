<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Services\SaleService;
use Illuminate\Http\Request;

class PosController extends Controller
{
    protected SaleService $saleService;

    public function __construct(SaleService $saleService)
    {
        $this->saleService = $saleService;
        $this->middleware('can:access-pos');
    }

    public function index(Request $request)
    {
        $products = Product::sellable()
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->with('brand')
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'price', 'stock_quantity', 'measurement_unit', 'attribute_values', 'brand_id', 'parent_id']);

        $customers = Customer::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'outstanding_balance', 'credit_limit']);

        return view('pos.checkout', compact('products', 'customers'));
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');

        $products = Product::sellable()
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%");
            })
            ->limit(15)
            ->get(['id', 'name', 'sku', 'price', 'stock_quantity', 'measurement_unit', 'attribute_values']);

        return response()->json($products);
    }

    public function checkout(Request $request)
    {
        $this->authorize('create', \App\Models\Sale::class);

        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:cash,mobile_money,credit,bank',
            'is_credit_sale' => 'boolean',
            'customer_id' => 'nullable|exists:customers,id',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $data['is_credit_sale'] = ($data['payment_method'] ?? '') === 'credit';

        $sale = $this->saleService->completeSale($request->user(), $data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'sale' => $sale,
                'message' => 'Sale completed successfully.',
            ]);
        }

        return redirect()->to(tenant_route('tenant.pos.index'))->with('success', 'Sale #' . $sale->sale_number . ' completed.');
    }
}
