<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierCreditPayment;
use App\Models\SupplierCreditPurchase;
use App\Services\PaymentWalletService;
use App\Services\SupplierCreditOverviewService;
use App\Services\SupplierCreditService;
use App\Support\ReportPeriodResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SupplierCreditController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:access-supplier-credit');
        $this->middleware('supplier.credit');
        $this->middleware('management.access');
    }

    public function index()
    {
        return redirect()->to(tenant_route('tenant.supplier-credit.overview.index'));
    }

    public function overview(Request $request, SupplierCreditOverviewService $overviewService)
    {
        $period = $request->input('period', 'daily');
        [$start, $end, $label] = ReportPeriodResolver::resolve($period, $request);

        return view('supplier-credit.overview.index', [
            'period' => $period,
            'label' => $label,
            'start' => $start,
            'end' => $end,
            'snapshot' => $overviewService->snapshot($start, $end),
            'totals' => $overviewService->totalsAllTime(),
            'timeline' => $overviewService->timeline($start, $end),
        ]);
    }

    public function bills(Request $request)
    {
        $status = $request->input('status');
        $allowed = [
            SupplierCreditPurchase::STATUS_OPEN,
            SupplierCreditPurchase::STATUS_PARTIAL,
            SupplierCreditPurchase::STATUS_PAID,
        ];

        $purchases = SupplierCreditPurchase::query()
            ->with(['supplier', 'lines.product'])
            ->when(in_array($status, $allowed, true), fn ($q) => $q->where('status', $status))
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->appends($status ? ['status' => $status] : []);

        $totalPayable = (float) SupplierCreditPurchase::query()
            ->whereIn('status', [SupplierCreditPurchase::STATUS_OPEN, SupplierCreditPurchase::STATUS_PARTIAL])
            ->selectRaw('COALESCE(SUM(total_amount - amount_paid), 0) as total')
            ->value('total');

        return view('supplier-credit.bills.index', [
            'purchases' => $purchases,
            'totalPayable' => $totalPayable,
            'statusFilter' => in_array($status, $allowed, true) ? $status : null,
        ]);
    }

    public function showBill(Request $request, Business $business, SupplierCreditPurchase $purchase)
    {
        if ((int) $purchase->business_id !== (int) $business->id) {
            abort(404);
        }

        $purchase->load([
            'supplier',
            'lines.product',
            'payments' => fn ($q) => $q->with('paymentWallet')->orderByDesc('paid_at')->orderByDesc('id'),
            'user',
        ]);

        $wallets = app(PaymentWalletService::class)->activeForBusiness((int) $business->id);

        return view('supplier-credit.bills.show', [
            'purchase' => $purchase,
            'balance' => $purchase->balanceDue(),
            'wallets' => $wallets,
        ]);
    }

    public function payments(Request $request)
    {
        $payments = SupplierCreditPayment::query()
            ->with(['supplier', 'purchase', 'user'])
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->paginate(30);

        return view('supplier-credit.payments.index', [
            'payments' => $payments,
        ]);
    }

    public function create(Request $request)
    {
        $business = $request->user()->business;

        $suppliers = Supplier::query()->where('is_active', true)->orderBy('name')->get();

        $search = trim((string) $request->input('search', ''));

        $products = Product::query()
            ->where('business_id', $business->id)
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%' . $search . '%')
                        ->orWhere('sku', 'like', '%' . $search . '%');
                });
            })
            ->with(['variants' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->paginate(20)
            ->appends(['search' => $search !== '' ? $search : null]);

        return view('supplier-credit.purchases.create', [
            'suppliers' => $suppliers,
            'products' => $products,
            'search' => $request->input('search'),
        ]);
    }

    public function store(Request $request, Business $business, SupplierCreditService $service)
    {
        $businessId = (int) $request->user()->business_id;

        $data = $request->validate([
            'supplier_id' => 'nullable',
            'supplier_name' => 'nullable|string|max:255',
            'product_id' => 'required|integer|exists:products,id',
            'variant_id' => 'nullable|integer',
            'quantity' => 'required|numeric|min:0.001',
            'unit_cost' => 'required|numeric|min:0',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:2000',
            'purchase_date' => 'nullable|date',
        ]);

        $product = Product::query()->where('business_id', $businessId)->findOrFail($data['product_id']);
        $target = $product;

        if ($product->isVariableParent()) {
            if (empty($data['variant_id'])) {
                return back()->withInput()->withErrors(['variant_id' => 'Select a variant to restock.']);
            }
            $target = $product->variants()->whereKey($data['variant_id'])->firstOrFail();
        }

        $supplier = $this->resolveSupplierForPurchase($businessId, $data['supplier_id'] ?? null, $data['supplier_name'] ?? null);

        $purchaseDate = ! empty($data['purchase_date']) ? new \DateTimeImmutable($data['purchase_date']) : null;

        $purchase = $service->recordCreditPurchase(
            $request->user(),
            $supplier,
            $target,
            (float) $data['quantity'],
            (float) $data['unit_cost'],
            $data['reference'] ?? null,
            $data['notes'] ?? null,
            $purchaseDate
        );

        return redirect()
            ->to(tenant_route('tenant.supplier-credit.bills.show', ['purchase' => $purchase]))
            ->with('success', 'Credit purchase recorded and stock updated.');
    }

    public function storePayment(
        Request $request,
        Business $business,
        SupplierCreditPurchase $purchase,
        SupplierCreditService $service,
        PaymentWalletService $walletService
    ) {
        if ((int) $purchase->business_id !== (int) $business->id) {
            abort(404);
        }

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:50',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:2000',
            'paid_at' => 'nullable|date',
            'payment_wallet_id' => 'nullable|integer',
        ]);

        $paidAt = ! empty($data['paid_at']) ? new \DateTimeImmutable($data['paid_at']) : null;

        $walletService->assertWalletRequired((int) $business->id, $data['payment_wallet_id'] ?? null);
        $wallet = $walletService->resolveForBusiness((int) $business->id, $data['payment_wallet_id'] ?? null);

        $service->recordPayment(
            $request->user(),
            $purchase,
            (float) $data['amount'],
            $data['payment_method'] ?? null,
            $data['reference'] ?? null,
            $data['notes'] ?? null,
            $paidAt,
            $wallet
        );

        return redirect()
            ->to(tenant_route('tenant.supplier-credit.bills.show', ['purchase' => $purchase->fresh()]))
            ->with('success', 'Payment recorded.');
    }

    public function destroyBill(Business $business, SupplierCreditPurchase $purchase, SupplierCreditService $service, Request $request)
    {
        if ((int) $purchase->business_id !== (int) $business->id) {
            abort(404);
        }

        $service->softDeleteBill($purchase, $request->user());

        return redirect()
            ->to(tenant_route('tenant.supplier-credit.bills.index'))
            ->with('success', 'Bill removed from active records. Stock levels were not changed.');
    }

    public function destroyPayment(Business $business, SupplierCreditPayment $payment, SupplierCreditService $service, Request $request)
    {
        if ((int) $payment->business_id !== (int) $business->id) {
            abort(404);
        }

        $service->softDeletePayment($payment, $request->user());

        return redirect()
            ->to(tenant_route('tenant.supplier-credit.payments.index'))
            ->with('success', 'Payment removed. The linked bill balance was updated.');
    }

    /**
     * @param  int|string|null  $supplierId
     */
    protected function resolveSupplierForPurchase(int $businessId, $supplierId, ?string $supplierName): Supplier
    {
        $supplierId = is_string($supplierId) ? trim($supplierId) : $supplierId;
        $name = trim((string) ($supplierName ?? ''));

        if ($supplierId !== null && $supplierId !== '' && $supplierId !== '__new__') {
            return Supplier::query()
                ->where('business_id', $businessId)
                ->whereKey((int) $supplierId)
                ->firstOrFail();
        }

        if ($name === '') {
            throw ValidationException::withMessages([
                'supplier_name' => 'Select a supplier or enter a name for a new one.',
            ]);
        }

        return Supplier::query()->firstOrCreate(
            [
                'business_id' => $businessId,
                'name' => $name,
            ],
            [
                'is_active' => true,
            ]
        );
    }
}
