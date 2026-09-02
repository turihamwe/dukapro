<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Services\ProductBatchService;
use App\Services\SaleService;
use Illuminate\Http\Request;

class PosController extends Controller
{
    protected SaleService $saleService;

    protected ProductBatchService $batchService;

    public function __construct(SaleService $saleService, ProductBatchService $batchService)
    {
        $this->saleService = $saleService;
        $this->batchService = $batchService;
        $this->middleware('can:access-pos');
    }

    public function index(Request $request)
    {
        $business = $request->user()->business;
        $waiterMode = $business->usesShiftWaiterMode();

        $products = Product::sellable()
            ->where('is_active', true)
            ->with('activeBatches')
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'price', 'stock_quantity', 'measurement_unit', 'attribute_values', 'brand_id', 'parent_id']);

        $products = $products->filter(function (Product $product) {
            return $this->batchService->availableStock($product) > 0;
        })->map(function (Product $product) {
            $product->setAttribute('available_stock', $this->batchService->availableStock($product));
            $product->setAttribute('fifo_price', $this->batchService->fifoSellingPrice($product));

            return $product;
        })->values();

        $customers = Customer::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'outstanding_balance', 'credit_limit']);

        $floorStaff = $waiterMode
            ? app(\App\Services\WaiterShiftService::class)->floorStaff($business)
            : collect();

        return view('pos.checkout', compact('products', 'customers', 'waiterMode', 'floorStaff'));
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');

        $products = Product::sellable()
            ->where('is_active', true)
            ->with('activeBatches')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%");
            })
            ->limit(15)
            ->get(['id', 'name', 'sku', 'price', 'stock_quantity', 'measurement_unit', 'attribute_values']);

        $products = $products->filter(function (Product $product) {
            return $this->batchService->availableStock($product) > 0;
        })->map(function (Product $product) {
            $product->setAttribute('available_stock', $this->batchService->availableStock($product));
            $product->setAttribute('fifo_price', $this->batchService->fifoSellingPrice($product));

            return $product;
        })->values();

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
            'mobile_money_provider' => 'nullable|in:airtel,mtn',
            'is_credit_sale' => 'boolean',
            'customer_id' => 'nullable|exists:customers,id',
            'waiter_id' => 'nullable|exists:users,id',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $business = $request->user()->business;
        if ($business->usesShiftWaiterMode()) {
            $request->validate(['waiter_id' => 'required|exists:users,id']);
            if (($data['payment_method'] ?? '') === 'mobile_money') {
                $request->validate(['mobile_money_provider' => 'required|in:airtel,mtn']);
            }
        }

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
