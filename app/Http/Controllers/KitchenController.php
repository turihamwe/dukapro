<?php

namespace App\Http\Controllers;

use App\Enums\KitchenOrderStatus;
use App\Models\Business;
use App\Models\KitchenOrder;
use App\Services\KitchenOrderService;
use Illuminate\Http\Request;

class KitchenController extends Controller
{
    protected KitchenOrderService $kitchenOrderService;

    public function __construct(KitchenOrderService $kitchenOrderService)
    {
        $this->kitchenOrderService = $kitchenOrderService;
    }

    public function index(Request $request)
    {
        $this->authorize('access-kitchen');

        $orders = $this->kitchenOrderService->listForKitchen($request->user());

        return view('kitchen.index', [
            'business' => $request->user()->business,
            'orders' => $orders,
            'statuses' => KitchenOrderStatus::all(),
        ]);
    }

    public function poll(Request $request)
    {
        $this->authorize('access-kitchen');

        $orders = $this->kitchenOrderService->listForKitchen($request->user());

        return response()->json([
            'orders' => $orders->map(fn (KitchenOrder $order) => $this->serializeOrder($order)),
        ]);
    }

    public function updateStatus(Request $request, Business $business, KitchenOrder $kitchenOrder)
    {
        $this->authorize('access-kitchen');

        $data = $request->validate([
            'status' => 'required|in:preparing,ready,cancelled',
        ]);

        $order = $this->kitchenOrderService->advanceStatus(
            $request->user(),
            $kitchenOrder,
            $data['status']
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'order' => $this->serializeOrder($order),
            ]);
        }

        return back()->with('success', 'Order updated to ' . KitchenOrderStatus::label($order->status) . '.');
    }

    public function readyOrders(Request $request)
    {
        $this->authorize('settle-kitchen-orders');

        $orders = $this->kitchenOrderService->listReadyForPayment($request->user());

        return view('kitchen.ready', [
            'business' => $request->user()->business,
            'orders' => $orders,
        ]);
    }

    public function settleForm(Business $business, KitchenOrder $kitchenOrder)
    {
        $this->authorize('settle-kitchen-orders');
        abort_unless($kitchenOrder->isActive() && ! $kitchenOrder->sale_id, 404);

        $kitchenOrder->load(['items', 'waiter']);

        return view('kitchen.settle', [
            'business' => $business,
            'order' => $kitchenOrder,
        ]);
    }

    public function settle(Request $request, Business $business, KitchenOrder $kitchenOrder)
    {
        $this->authorize('settle-kitchen-orders');

        $payment = $request->validate([
            'payment_method' => 'required|in:cash,mobile_money,credit,bank',
            'mobile_money_provider' => 'nullable|in:airtel,mtn',
            'is_credit_sale' => 'boolean',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        if ($payment['payment_method'] === 'mobile_money') {
            $request->validate(['mobile_money_provider' => 'required|in:airtel,mtn']);
        }

        $payment['is_credit_sale'] = $payment['payment_method'] === 'credit';

        $sale = $this->kitchenOrderService->settleOrder($request->user(), $kitchenOrder, $payment);

        return redirect()
            ->to(tenant_route('tenant.kitchen.ready'))
            ->with('success', 'Payment collected. Sale #' . $sale->sale_number . ' recorded.');
    }

    protected function serializeOrder(KitchenOrder $order): array
    {
        $order->loadMissing(['items', 'waiter']);

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'table_label' => $order->tableDisplay(),
            'status' => $order->status,
            'status_label' => KitchenOrderStatus::label($order->status),
            'is_paid' => $order->isPaid(),
            'subtotal' => $order->subtotal,
            'placed_at' => optional($order->placed_at)->toIso8601String(),
            'waiter' => $order->waiter ? $order->waiter->name : null,
            'items' => $order->items->map(fn ($item) => [
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'subtotal' => $item->subtotal,
                'notes' => $item->notes,
            ]),
            'update_url' => tenant_route('tenant.kitchen.update-status', ['kitchenOrder' => $order]),
        ];
    }
}
