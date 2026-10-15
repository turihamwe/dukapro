<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierCreditPayment;
use App\Models\SupplierCreditPurchase;
use Illuminate\Support\Carbon;
use App\Services\PaymentWalletService;
use App\Services\SupplierCreditListReportService;
use App\Services\SupplierCreditOverviewService;
use App\Services\SupplierCreditService;
use App\Support\CsvExport;
use App\Support\ReportPeriodResolver;
use App\Support\ReportShareMessages;
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

    public function bills(Request $request, PaymentWalletService $walletService)
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

        $businessId = (int) $request->user()->business_id;

        $suppliers = Supplier::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $reportService = app(SupplierCreditListReportService::class);
        $statusFilter = in_array($status, $allowed, true) ? $status : null;
        $reportQuery = $statusFilter ? ['status' => $statusFilter] : [];
        $business = $request->user()->business;
        $reportPurchases = $reportService->billsForReport($request);
        $reportTotals = $reportService->billsTotals($reportPurchases);
        $filterLabel = $reportService->billsFilterLabel($statusFilter);
        $shareMessage = ReportShareMessages::vendorBillsList($business, $filterLabel, $reportTotals);
        $shareSubject = 'Vendor bills — ' . $business->name;

        return view('supplier-credit.bills.index', [
            'purchases' => $purchases,
            'totalPayable' => $totalPayable,
            'statusFilter' => $statusFilter,
            'wallets' => $walletService->activeForBusiness($businessId),
            'suppliers' => $suppliers,
            'reportQuery' => $reportQuery,
            'shareWhatsAppUrl' => whatsapp_share_url(null, $shareMessage),
            'shareEmailUrl' => mailto_share_url($business->email, $shareSubject, $shareMessage),
            'shareMessage' => $shareMessage,
        ]);
    }

    public function billsPrint(Request $request, Business $business, SupplierCreditListReportService $reportService)
    {
        $statusFilter = $reportService->billsStatusFilter($request);
        $purchases = $reportService->billsForReport($request);
        $totals = $reportService->billsTotals($purchases);
        $filterLabel = $reportService->billsFilterLabel($statusFilter);
        $shareMessage = ReportShareMessages::vendorBillsList($business, $filterLabel, $totals);

        return view('supplier-credit.reports.bills-print', [
            'business' => $business,
            'purchases' => $purchases,
            'totals' => $totals,
            'filterLabel' => $filterLabel,
            'generatedAt' => now(),
            'shareWhatsAppUrl' => whatsapp_share_url(null, $shareMessage),
            'shareEmailUrl' => mailto_share_url($business->email, 'Vendor bills — ' . $business->name, $shareMessage),
            'shareMessage' => $shareMessage,
        ]);
    }

    public function billsExport(Request $request, SupplierCreditListReportService $reportService)
    {
        $purchases = $reportService->billsForReport($request);
        $rows = $purchases->map(function (SupplierCreditPurchase $purchase) {
            return [
                $purchase->purchase_date->format('Y-m-d'),
                optional($purchase->supplier)->name ?? '',
                $purchase->reference ?? '',
                number_format((float) $purchase->total_amount, 2, '.', ''),
                number_format((float) $purchase->amount_paid, 2, '.', ''),
                number_format($purchase->balanceDue(), 2, '.', ''),
                ucfirst($purchase->status),
                $purchase->is_historical_adjustment ? 'Yes' : 'No',
                $purchase->notes ?? '',
            ];
        });

        return CsvExport::download('vendor-bills-' . now()->format('Y-m-d') . '.csv', [
            'Bill date',
            'Vendor',
            'Reference',
            'Total amount',
            'Amount paid',
            'Balance due',
            'Status',
            'Historical adjustment',
            'Notes',
        ], $rows);
    }

    public function storeManualBill(Request $request, Business $business, SupplierCreditService $service)
    {
        $businessId = (int) $request->user()->business_id;

        $data = $request->validate([
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'amount' => 'required|numeric|min:0.01',
            'purchase_date' => 'required|date|before_or_equal:today',
            'reference' => 'nullable|string|max:100',
            'source_note' => 'nullable|string|max:2000',
            'is_historical_adjustment' => 'nullable|boolean',
        ]);

        $supplier = Supplier::query()
            ->where('business_id', $businessId)
            ->whereKey($data['supplier_id'])
            ->firstOrFail();

        $purchaseDate = Carbon::parse($data['purchase_date']);
        $isHistorical = $request->boolean('is_historical_adjustment');

        $notes = trim((string) ($data['source_note'] ?? ''));
        if ($isHistorical && $notes === '') {
            $notes = 'Historical / adjustment bill (no stock movement).';
        } elseif ($isHistorical) {
            $notes = 'Historical / adjustment: ' . $notes;
        }

        $purchase = $service->recordManualVendorBill(
            $request->user(),
            $supplier,
            (float) $data['amount'],
            $purchaseDate,
            $isHistorical,
            $data['reference'] ?? null,
            $notes !== '' ? $notes : null
        );

        return redirect()
            ->to(tenant_route('tenant.supplier-credit.bills.show', ['purchase' => $purchase]))
            ->with('success', 'Vendor bill recorded. Payables updated with no inventory change.');
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
        $balance = $purchase->balanceDue();
        $shareMessage = \App\Support\ReportShareMessages::supplierBill($business, $purchase, $balance);
        $vendor = optional($purchase->supplier)->name ?? 'Vendor';
        $shareSubject = 'Supplier bill — ' . $vendor . ' — ' . $business->name;

        return view('supplier-credit.bills.show', [
            'business' => $business,
            'purchase' => $purchase,
            'balance' => $balance,
            'wallets' => $wallets,
            'shareWhatsAppUrl' => whatsapp_share_url(optional($purchase->supplier)->phone, $shareMessage),
            'shareEmailUrl' => mailto_share_url(optional($purchase->supplier)->email ?: $business->email, $shareSubject, $shareMessage),
            'shareMessage' => $shareMessage,
        ]);
    }

    public function payments(Request $request)
    {
        $payments = SupplierCreditPayment::query()
            ->with(['supplier', 'purchase', 'user'])
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->paginate(30);

        $reportService = app(SupplierCreditListReportService::class);
        $business = $request->user()->business;
        $reportPayments = $reportService->paymentsForReport($request);
        $reportTotals = $reportService->paymentsTotals($reportPayments);
        $shareMessage = ReportShareMessages::vendorPaymentsList($business, $reportTotals);
        $shareSubject = 'Vendor payments — ' . $business->name;

        return view('supplier-credit.payments.index', [
            'payments' => $payments,
            'reportQuery' => [],
            'shareWhatsAppUrl' => whatsapp_share_url(null, $shareMessage),
            'shareEmailUrl' => mailto_share_url($business->email, $shareSubject, $shareMessage),
            'shareMessage' => $shareMessage,
        ]);
    }

    public function paymentsPrint(Request $request, Business $business, SupplierCreditListReportService $reportService)
    {
        $payments = $reportService->paymentsForReport($request);
        $totals = $reportService->paymentsTotals($payments);
        $shareMessage = ReportShareMessages::vendorPaymentsList($business, $totals);

        return view('supplier-credit.reports.payments-print', [
            'business' => $business,
            'payments' => $payments,
            'totals' => $totals,
            'generatedAt' => now(),
            'shareWhatsAppUrl' => whatsapp_share_url(null, $shareMessage),
            'shareEmailUrl' => mailto_share_url($business->email, 'Vendor payments — ' . $business->name, $shareMessage),
            'shareMessage' => $shareMessage,
        ]);
    }

    public function paymentsExport(Request $request, SupplierCreditListReportService $reportService)
    {
        $payments = $reportService->paymentsForReport($request);

        $rows = $payments->map(function (SupplierCreditPayment $payment) {
            return [
                $payment->paid_at->format('Y-m-d H:i'),
                optional($payment->supplier)->name ?? '',
                optional($payment->purchase)->reference ?? ($payment->purchase ? '#' . $payment->purchase->id : ''),
                number_format((float) $payment->amount, 2, '.', ''),
                $payment->payment_method ?? '',
                $payment->reference ?? '',
                optional($payment->paymentWallet)->name ?? '',
                optional($payment->user)->name ?? '',
                $payment->notes ?? '',
            ];
        });

        return CsvExport::download('vendor-payments-' . now()->format('Y-m-d') . '.csv', [
            'Paid at',
            'Vendor',
            'Bill reference',
            'Amount',
            'Payment method',
            'Reference',
            'Wallet',
            'Recorded by',
            'Notes',
        ], $rows);
    }

    public function create(Request $request)
    {
        $business = $request->user()->business;

        $suppliers = Supplier::query()->where('is_active', true)->orderBy('name')->get();

        $catalog = $this->receiveProductCatalog();
        $catalogById = collect($catalog)->keyBy('id');
        $oldLines = old('lines');
        $initialLines = collect(is_array($oldLines) && $oldLines !== [] ? $oldLines : [[]])
            ->map(function ($line) use ($catalogById) {
                $product = $catalogById->get((int) ($line['product_id'] ?? 0));

                return [
                    'product_id' => $product['id'] ?? '',
                    'query' => $product['name'] ?? '',
                    'name' => $product['name'] ?? '',
                    'sku' => $product['sku'] ?? '',
                    'quantity' => $line['quantity'] ?? '',
                    'unit_cost' => $line['unit_cost'] ?? ($product['unit_cost'] ?? ''),
                    'previous_cost' => $product['previous_cost'] ?? null,
                    'previous_change' => $product['previous_change'] ?? null,
                    'unit_label' => $product['unit_label'] ?? 'unit',
                ];
            })
            ->values()
            ->all();

        return view('supplier-credit.purchases.create', [
            'suppliers' => $suppliers,
            'catalog' => $catalog,
            'initialLines' => $initialLines,
            'currencySample' => format_money(0, $business),
        ]);
    }

    public function store(Request $request, Business $business, SupplierCreditService $service)
    {
        if (is_array($request->input('lines'))) {
            return $this->storeMultiLineBill($request, $service);
        }

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

        return back()->with('success', 'Payment recorded.');
    }

    public function destroyBill(Business $business, SupplierCreditPurchase $purchase, SupplierCreditService $service, Request $request)
    {
        if ((int) $purchase->business_id !== (int) $business->id) {
            abort(404);
        }

        $service->softDeleteBill($purchase, $request->user());

        return redirect()
            ->to(tenant_route('tenant.supplier-credit.bills.index'))
            ->with('success', 'Bill deleted.');
    }

    public function destroyPayment(Business $business, SupplierCreditPayment $payment, SupplierCreditService $service, Request $request)
    {
        if ((int) $payment->business_id !== (int) $business->id) {
            abort(404);
        }

        $service->softDeletePayment($payment, $request->user());

        return redirect()
            ->to(tenant_route('tenant.supplier-credit.payments.index'))
            ->with('success', 'Payment deleted.');
    }

    /**
     * @param  int|string|null  $supplierId
     */
    protected function storeMultiLineBill(Request $request, SupplierCreditService $service)
    {
        $businessId = (int) $request->user()->business_id;

        $data = $request->validate([
            'supplier_id' => 'nullable',
            'supplier_name' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:2000',
            'purchase_date' => 'nullable|date',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'required|integer',
            'lines.*.quantity' => 'required|numeric|min:0.001',
            'lines.*.unit_cost' => 'required|numeric|min:0',
        ]);

        $supplier = $this->resolveSupplierForPurchase($businessId, $data['supplier_id'] ?? null, $data['supplier_name'] ?? null);
        $purchaseDate = ! empty($data['purchase_date']) ? new \DateTimeImmutable($data['purchase_date']) : null;

        $purchase = $service->recordCreditPurchaseLines(
            $request->user(),
            $supplier,
            $data['lines'],
            $data['reference'] ?? null,
            $data['notes'] ?? null,
            $purchaseDate
        );

        return redirect()
            ->to(tenant_route('tenant.supplier-credit.bills.show', ['purchase' => $purchase]))
            ->with('success', 'Bill ' . $purchase->reference . ' saved. Stock updated and the supplier balance increased.');
    }

    /**
     * @return array<int, array{id: int, name: string, sku: string, unit_cost: float}>
     */
    protected function receiveProductCatalog(): array
    {
        $products = Product::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->with(['variants' => function ($query) {
                $query->where('is_active', true)->orderBy('name');
            }])
            ->orderBy('name')
            ->get();

        $catalog = [];

        foreach ($products as $product) {
            if ($product->isService()) {
                continue;
            }

            if ($product->isVariableParent()) {
                foreach ($product->variants as $variant) {
                    if ($variant->isService()) {
                        continue;
                    }
                    $catalog[] = $this->receiveCatalogRow($variant);
                }
                continue;
            }

            $catalog[] = $this->receiveCatalogRow($product);
        }

        return $catalog;
    }

    /**
     * @return array{id: int, name: string, sku: string, unit_cost: float, previous_cost: ?float, previous_change: ?float, unit_label: string}
     */
    protected function receiveCatalogRow(Product $product): array
    {
        $defaultCost = \App\Support\ProductInventoryValuation::purchaseDefaultUnitCost($product);
        $canViewCosts = auth()->user() && auth()->user()->can('view-cost-prices');
        $previousCost = $canViewCosts
            ? \App\Support\ProductInventoryValuation::lastPurchaseUnitCost($product)
            : null;

        return [
            'id' => (int) $product->id,
            'name' => $product->displayName(),
            'sku' => (string) ($product->sku ?? ''),
            'unit_cost' => round($defaultCost, 2),
            'previous_cost' => $previousCost !== null ? round($previousCost, 2) : null,
            'previous_change' => $canViewCosts
                ? \App\Support\ProductInventoryValuation::percentChangeFromDefault(
                    $previousCost,
                    $defaultCost > 0 ? $defaultCost : null
                )
                : null,
            'unit_label' => $product->measurement_unit ?: 'unit',
        ];
    }

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
