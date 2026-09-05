<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Business;
use App\Models\KitchenOrder;
use App\Services\KitchenOrderService;
use Illuminate\Http\Request;

class RestaurantOrderController extends Controller
{
    protected KitchenOrderService $kitchenOrderService;

    public function __construct(KitchenOrderService $kitchenOrderService)
    {
        $this->kitchenOrderService = $kitchenOrderService;
        $this->middleware('can:view-restaurant-orders');
    }

    public function index(Request $request)
    {
        $business = $request->user()->business;
        abort_unless($business && $business->usesRestaurantMode(), 404);

        $orders = $this->kitchenOrderService->listForCashier($request->user());

        return view('restaurant-orders.index', compact('business', 'orders'));
    }

    public function print(Business $business, KitchenOrder $kitchenOrder)
    {
        abort_unless((int) $kitchenOrder->business_id === (int) $business->id, 404);

        $kitchenOrder->load(['items', 'waiter', 'restaurantTable', 'sale']);

        $documentType = $kitchenOrder->isPaid() ? 'receipt' : 'invoice';

        return view('restaurant-orders.print', [
            'business' => $business,
            'order' => $kitchenOrder,
            'documentType' => $documentType,
        ]);
    }
}
