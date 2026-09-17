<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use App\Models\Customer;
use App\Models\Product;
use App\Services\CustomerService;
use App\Services\KitchenOrderService;
use App\Services\LowStockAlertService;
use App\Services\ProductBatchService;
use App\Services\ProductUnitService;
use App\Services\SaleService;
use App\Support\DivisibleProductsMode;
use App\Support\SaleDocument;
use App\Support\VariablePricingMode;
use Illuminate\Http\Request;

class PosController extends Controller
{
    protected SaleService $saleService;

    protected ProductBatchService $batchService;

    protected KitchenOrderService $kitchenOrderService;

    protected LowStockAlertService $lowStockAlertService;

    protected ProductUnitService $unitService;

    protected CustomerService $customerService;

    public function __construct(
        SaleService $saleService,
        ProductBatchService $batchService,
        KitchenOrderService $kitchenOrderService,
        LowStockAlertService $lowStockAlertService,
        ProductUnitService $unitService,
        CustomerService $customerService
    ) {
        $this->saleService = $saleService;
        $this->batchService = $batchService;
        $this->kitchenOrderService = $kitchenOrderService;
        $this->lowStockAlertService = $lowStockAlertService;
        $this->unitService = $unitService;
        $this->customerService = $customerService;
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
            ->with(['activeBatches', 'units'])
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'price', 'stock_quantity', 'measurement_unit', 'attribute_values', 'brand_id', 'parent_id']);

        $products = $products->filter(function (Product $product) {
            return $this->batchService->availableStock($product) > 0;
        })->map(function (Product $product) {
            $baseStock = $this->batchService->availableStock($product);
            $product->setAttribute('available_stock', $baseStock);
            $product->setAttribute('fifo_price', $this->batchService->fifoSellingPrice($product));
            $product->setAttribute('pos_units', $this->unitService->posCatalogUnits($product));

            return $product;
        })->values();

        $customers = Customer::where('is_active', true)
            ->where('is_credit_customer', true)
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'outstanding_balance', 'credit_limit', 'payment_terms_days']);

        $floorStaff = $waiterMode
            ? app(\App\Services\WaiterShiftService::class)->activeFloorStaff($business, $request->user())
            : collect();

        $lowStockItems = $this->lowStockAlertService->lowStockProducts($business, $request->user(), 8);

        $variablePricingMode = VariablePricingMode::active($business);
        $divisibleProductsMode = DivisibleProductsMode::active($business);

        return view('pos.checkout', compact('products', 'customers', 'waiterMode', 'restaurantMode', 'isHospitality', 'useRestaurantTables', 'restaurantTables', 'floorStaff', 'lowStockItems', 'variablePricingMode', 'divisibleProductsMode'));
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');

        $products = Product::sellable()
            ->where('is_active', true)
            ->with(['activeBatches', 'units'])
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%");
            })
            ->limit(15)
            ->get(['id', 'name', 'sku', 'price', 'stock_quantity', 'measurement_unit', 'attribute_values']);

        $products = $products->filter(function (Product $product) {
            return $this->batchService->availableStock($product) > 0;
        })->map(function (Product $product) {
            $baseStock = $this->batchService->availableStock($product);
            $product->setAttribute('available_stock', $baseStock);
            $product->setAttribute('fifo_price', $this->batchService->fifoSellingPrice($product));
            $product->setAttribute('pos_units', $this->unitService->posCatalogUnits($product));

            return $product;
        })->values();

        return response()->json($products);
    }

    public function quickStoreCustomer(Request $request)
    {
        $business = $request->user()->business;
        $businessId = (int) $business->id;

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms_days' => 'nullable|integer|min:1|max:365',
        ]);

        $result = $this->customerService->findOrCreate($businessId, [
            'name' => $data['name'],
            'phone' => $data['phone'],
            'credit_limit' => $data['credit_limit'] ?? 0,
            'payment_terms_days' => $data['payment_terms_days'] ?? 30,
            'is_credit_customer' => true,
        ], $request->user(), true);

        $customer = $result['customer'];

        if ($result['created']) {
            AuditLogger::record('contact_created', $customer, null, $customer->toArray());
        }

        return response()->json([
            'success' => true,
            'created' => $result['created'],
            'message' => $result['created']
                ? 'Credit customer saved.'
                : 'Existing customer matched by phone number.',
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'outstanding_balance' => (float) $customer->outstanding_balance,
                'credit_limit' => (float) $customer->credit_limit,
                'payment_terms_days' => (int) $customer->payment_terms_days,
            ],
        ], $result['created'] ? 201 : 200);
    }

    public function checkout(Request $request)
    {
        $this->authorize('create', \App\Models\Sale::class);

        $business = $request->user()->business;
        $quantityRules = DivisibleProductsMode::quantityValidationRules($business);

        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => $quantityRules,
            'items.*.product_unit_id' => 'nullable|integer|exists:product_units,id',
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

        if (VariablePricingMode::active($business)) {
            $request->validate([
                'items.*.unit_price' => 'required|numeric|min:0',
            ]);
        }

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

        $sale = $sale->fresh(['customer', 'items']);
        $documentUrl = SaleDocument::url($sale);
        $customerPhone = optional($sale->customer)->phone;

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'sale' => $sale,
                'message' => SaleDocument::isInvoice($sale)
                    ? 'Credit sale recorded. Invoice generated.'
                    : 'Sale completed successfully.',
                'document_type' => SaleDocument::type($sale),
                'receipt_url' => $documentUrl,
                'receipt_message' => SaleDocument::message($sale),
                'customer_phone' => $customerPhone,
            ]);
        }

        return redirect()
            ->to($documentUrl)
            ->with('success', 'Sale #' . $sale->sale_number . ' completed.');
    }

    public function sendToKitchen(Request $request)
    {
        $business = $request->user()->business;
        abort_unless($business && $business->usesRestaurantMode(), 403);

        $quantityRules = DivisibleProductsMode::quantityValidationRules($business);

        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => $quantityRules,
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
