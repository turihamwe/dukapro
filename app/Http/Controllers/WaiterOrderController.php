<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\KitchenOrderService;
use Illuminate\Http\Request;

class WaiterOrderController extends Controller
{
    protected KitchenOrderService $kitchenOrderService;

    public function __construct(KitchenOrderService $kitchenOrderService)
    {
        $this->kitchenOrderService = $kitchenOrderService;
        $this->middleware('can:access-waiter-orders');
    }

    public function index(Request $request)
    {
        $business = $request->user()->business;
        $useRestaurantTables = $business->usesTableSeating();
        $restaurantTables = $useRestaurantTables
            ? app(\App\Services\RestaurantTableService::class)->optionsForOrder($request->user())
            : [];

        $products = Product::sellable()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'price', 'measurement_unit']);

        return view('waiter-orders.index', compact('business', 'products', 'useRestaurantTables', 'restaurantTables'));
    }

    public function search(Request $request)
    {
        $query = trim((string) $request->get('q', ''));

        $products = Product::sellable()
            ->where('is_active', true)
            ->when($query !== '', function ($q) use ($query) {
                $q->where(function ($inner) use ($query) {
                    $inner->where('name', 'like', "%{$query}%")
                        ->orWhere('sku', 'like', "%{$query}%");
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'sku', 'price', 'measurement_unit']);

        return response()->json($products);
    }

    public function place(Request $request)
    {
        $data = $request->validate([
            'table_label' => 'nullable|string|max:50',
            'restaurant_table_id' => 'nullable|integer|exists:restaurant_tables,id',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        $order = $this->kitchenOrderService->placeOrder($request->user(), $data);

        return response()->json([
            'success' => true,
            'message' => 'Order sent to kitchen.',
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'table_label' => $order->table_label,
                'subtotal' => $order->subtotal,
                'item_count' => $order->items->count(),
            ],
        ], 201);
    }
}
