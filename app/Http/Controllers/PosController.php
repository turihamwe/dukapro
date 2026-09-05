<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Services\KitchenOrderService;
use App\Services\ProductBatchService;
use App\Services\SaleService;
use Illuminate\Http\Request;

class PosController extends Controller
{
    protected SaleService $saleService;

    protected ProductBatchService $batchService;

    protected KitchenOrderService $kitchenOrderService;

    public function __construct(
        SaleService $saleService,
        ProductBatchService $batchService,
        KitchenOrderService $kitchenOrderService
    ) {
        $this->saleService = $saleService;
        $this->batchService = $batchService;
        $this->kitchenOrderService = $kitchenOrderService;
        $this->middleware('can:access-pos');
    }

    public function index(Request $request)
    {
        $business = $request->user()->business;
        $waiterMode = $business->usesWaiterAssignment();
        $restaurantMode = $business->usesRestaurantMode();
        $isHospitality = $business->isHospitality();
        $useRestaurantTables = $business->usesTableSeating();
        $restaurantTables = $useRestaurantTables
            ? app(\App\Services\RestaurantTableService::class)->optionsForOrder($request->user())
            : [];

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
            ? app(\App\Services\WaiterShiftService::class)->floorStaff($business, $request->user())
            : collect();

        return view('pos.checkout', compact('products', 'customers', 'waiterMode', 'restaurantMode', 'isHospitality', 'useRestaurantTables', 'restaurantTables', 'floorStaff'));
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
            'items.*.notes' => 'nullable|string|max:500',
            'payment_method' => 'required|in:cash,mobile_money,credit,bank',
            'mobile_money_provider' => 'nullable|in:airtel,mtn',
            'is_credit_sale' => 'boolean',
            'customer_id' => 'nullable|exists:customers,id',
            'waiter_id' => 'nullable|exists:users,id',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
            'table_label' => 'nullable|string|max:50',
            'restaurant_table_id' => 'nullable|integer|exists:restaurant_tables,id',
        ]);

        $business = $request->user()->business;
        if ($business->usesWaiterAssignment()) {
            $request->validate(['waiter_id' => 'required|exists:users,id']);
            if (($data['payment_method'] ?? '') === 'mobile_money') {
                $request->validate(['mobile_money_provider' => 'required|in:airtel,mtn']);
            }
            app(\App\Services\WaiterShiftService::class)->resolveAssignableFloorStaff(
                $business,
                $request->user(),
                (int) $data['waiter_id']
            );
        }

        $data['is_credit_sale'] = ($data['payment_method'] ?? '') === 'credit';

        $sale = $this->saleService->completeSale($request->user(), $data);

        if ($business && $business->usesRestaurantMode()) {
            $this->kitchenOrderService->recordCounterSaleOrder($request->user(), $sale, $data);
            $sale = $sale->fresh(['items']);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'sale' => $sale,
                'message' => 'Sale completed successfully.',
            ]);
        }

        return redirect()->to(tenant_route('tenant.pos.index'))->with('success', 'Sale #' . $sale->sale_number . ' completed.');
    }

    public function sendToKitchen(Request $request)
    {
        $business = $request->user()->business;
        abort_unless($business && $business->usesRestaurantMode(), 403);

        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.notes' => 'nullable|string|max:500',
            'table_label' => 'nullable|string|max:50',
            'restaurant_table_id' => 'nullable|integer|exists:restaurant_tables,id',
            'notes' => 'nullable|string|max:500',
            'waiter_id' => 'nullable|exists:users,id',
        ]);

        if ($business->usesWaiterAssignment()) {
            $request->validate(['waiter_id' => 'required|exists:users,id']);
            app(\App\Services\WaiterShiftService::class)->resolveAssignableFloorStaff(
                $business,
                $request->user(),
                (int) $data['waiter_id']
            );
        }

        $order = $this->kitchenOrderService->placeOrder($request->user(), $data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Order sent to kitchen.',
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'table_label' => $order->table_label,
                ],
            ], 201);
        }

        return redirect()
            ->to(tenant_route('tenant.pos.index'))
            ->with('success', 'Order ' . $order->order_number . ' sent to kitchen.');
    }
}
