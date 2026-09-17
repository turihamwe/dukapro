<?php

namespace App\Services;

use App\Enums\DebtEntryType;
use App\Helpers\AuditLogger;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemBatchAllocation;
use App\Models\User;
use App\Scopes\BranchScope;
use App\Support\DivisibleProductsMode;
use App\Support\VariablePricingMode;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    protected DebtLedgerService $debtLedgerService;

    protected ProductBatchService $batchService;

    protected BranchResolver $branchResolver;

    protected ProductUnitService $unitService;

    public function __construct(
        DebtLedgerService $debtLedgerService,
        ProductBatchService $batchService,
        BranchResolver $branchResolver,
        ProductUnitService $unitService
    ) {
        $this->debtLedgerService = $debtLedgerService;
        $this->batchService = $batchService;
        $this->branchResolver = $branchResolver;
        $this->unitService = $unitService;
    }

    public function completeSale(User $user, array $payload): Sale
    {
        $sale = DB::transaction(function () use ($user, $payload) {
            $businessId = $user->business_id;
            $items = $payload['items'];
            $paymentMethod = $payload['payment_method'] ?? 'cash';
            $isCreditSale = (bool) ($payload['is_credit_sale'] ?? false);
            $customerId = $payload['customer_id'] ?? null;
            $waiterId = $payload['waiter_id'] ?? null;
            $mobileProvider = $payload['mobile_money_provider'] ?? null;
            $business = $user->business;
            $waiterMode = $business && $business->usesWaiterAssignment();

            if ($waiterMode && ! $waiterId) {
                throw ValidationException::withMessages([
                    'waiter_id' => 'Select the waiter or floor staff for this order.',
                ]);
            }

            if ($isCreditSale && ! $customerId && ! $waiterMode) {
                throw ValidationException::withMessages([
                    'customer_id' => 'A customer is required for credit sales.',
                ]);
            }

            if ($waiterId) {
                if ($waiterMode) {
                    app(WaiterShiftService::class)->resolveAssignableFloorStaff(
                        $business,
                        $user,
                        (int) $waiterId
                    );
                } else {
                    $waiter = User::query()
                        ->where('business_id', $businessId)
                        ->where('id', $waiterId)
                        ->where('is_active', true)
                        ->first();

                    if (! $waiter) {
                        throw ValidationException::withMessages([
                            'waiter_id' => 'Selected staff member is invalid.',
                        ]);
                    }
                }
            }

            $staffBranchId = $this->branchResolver->forUser($user);
            $variablePricing = $business && VariablePricingMode::active($business);
            $subtotal = 0;
            $lineItems = [];
            $resolvedProducts = collect();

            foreach ($items as $item) {
                $productQuery = Product::query()
                    ->withoutGlobalScope(BranchScope::class)
                    ->where('business_id', $businessId)
                    ->where('id', $item['product_id']);

                if ($staffBranchId) {
                    $productQuery->where('branch_id', $staffBranchId);
                }

                $product = $productQuery->lockForUpdate()->first();

                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => 'One or more products are unavailable at your branch.',
                    ]);
                }

                $resolvedProducts->push($product);

                $soldQuantity = round((float) $item['quantity'], 3);
                DivisibleProductsMode::assertWholeQuantity($business, $soldQuantity, $product->displayName());
                $productUnit = $this->unitService->resolveUnit($product, $item['product_unit_id'] ?? null);
                $baseQuantity = $this->unitService->toBaseQuantity($productUnit, $soldQuantity);
                $available = $this->batchService->availableStock($product);

                if ($available < $baseQuantity) {
                    $maxInUnit = $productUnit->maxSellableQuantity($available);
                    throw ValidationException::withMessages([
                        'items' => "Insufficient stock for {$product->displayName()}. Available: {$maxInUnit} {$productUnit->unit_name}",
                    ]);
                }

                $deduction = $this->batchService->applyFifoDeduction($product, $baseQuantity);

                if ($variablePricing) {
                    if (! isset($item['unit_price']) || ! is_numeric($item['unit_price'])) {
                        throw ValidationException::withMessages([
                            'items' => 'Each cart item must include a negotiated price when variable pricing is enabled.',
                        ]);
                    }

                    $unitPrice = round((float) $item['unit_price'], 2);

                    if ($unitPrice < 0) {
                        throw ValidationException::withMessages([
                            'items' => 'Item prices cannot be negative.',
                        ]);
                    }

                    $lineSubtotal = round($soldQuantity * $unitPrice, 2);
                } else {
                    $lineSubtotal = $deduction['subtotal'];
                    $unitPrice = $soldQuantity > 0
                        ? round($lineSubtotal / $soldQuantity, 2)
                        : $deduction['unit_price'];
                }

                $subtotal += $lineSubtotal;

                $lineItems[] = [
                    'product' => $product->fresh(),
                    'product_unit' => $productUnit,
                    'quantity' => $soldQuantity,
                    'base_quantity' => $baseQuantity,
                    'unit_price' => $unitPrice,
                    'cost_price' => $deduction['cost_price'],
                    'subtotal' => $lineSubtotal,
                    'allocations' => $deduction['allocations'],
                    'notes' => ! empty($item['notes']) ? mb_substr(trim((string) $item['notes']), 0, 500) : null,
                ];
            }

            $taxAmount = (float) ($payload['tax_amount'] ?? 0);
            $discountAmount = (float) ($payload['discount_amount'] ?? 0);
            $total = round($subtotal + $taxAmount - $discountAmount, 2);

            $creditCustomer = null;
            $invoiceDueAt = null;

            if ($isCreditSale && $customerId) {
                $creditCustomer = Customer::where('business_id', $businessId)
                    ->where('id', $customerId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $creditLimit = (float) $creditCustomer->credit_limit;
                if ($creditLimit > 0 && ($creditCustomer->outstanding_balance + $total) > $creditLimit) {
                    throw ValidationException::withMessages([
                        'customer_id' => 'Credit limit exceeded for this customer.',
                    ]);
                }

                $termsDays = max(1, (int) ($creditCustomer->payment_terms_days ?? 30));
                $invoiceDueAt = Carbon::now()->addDays($termsDays);
            }

            $saleBranchId = $this->resolveSaleBranchId($user, $resolvedProducts);

            $saleNumber = $this->generateSaleNumber($businessId);

            $sale = Sale::create([
                'business_id' => $businessId,
                'branch_id' => $saleBranchId,
                'user_id' => $user->id,
                'waiter_id' => $waiterId,
                'customer_id' => $customerId,
                'kitchen_order_id' => $payload['kitchen_order_id'] ?? null,
                'sale_number' => $saleNumber,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total' => $total,
                'payment_method' => $isCreditSale ? 'credit' : $paymentMethod,
                'mobile_money_provider' => (! $isCreditSale && $paymentMethod === 'mobile_money') ? $mobileProvider : null,
                'is_credit_sale' => $isCreditSale,
                'status' => 'completed',
                'notes' => $payload['notes'] ?? null,
                'completed_at' => Carbon::now(),
                'invoice_due_at' => $invoiceDueAt,
            ]);

            foreach ($lineItems as $line) {
                /** @var Product $product */
                $product = $line['product'];

                /** @var \App\Models\ProductUnit|null $productUnit */
                $productUnit = $line['product_unit'] ?? null;

                $saleItem = SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'product_unit_id' => $productUnit ? $productUnit->id : null,
                    'product_name' => $product->displayName(),
                    'sku' => $product->sku,
                    'variant_attributes' => $product->attribute_values ?? $product->variant_attributes,
                    'measurement_unit' => $productUnit ? $productUnit->unit_name : $product->measurement_unit,
                    'quantity' => $line['quantity'],
                    'base_quantity' => $line['base_quantity'],
                    'unit_price' => $line['unit_price'],
                    'cost_price' => $line['cost_price'],
                    'discount_amount' => 0,
                    'subtotal' => $line['subtotal'],
                    'notes' => $line['notes'] ?? null,
                ]);

                foreach ($line['allocations'] as $allocation) {
                    SaleItemBatchAllocation::create([
                        'sale_item_id' => $saleItem->id,
                        'product_batch_id' => $allocation['product_batch_id'],
                        'quantity' => $allocation['quantity'],
                        'cost_price' => $allocation['cost_price'],
                        'selling_price' => $allocation['selling_price'],
                        'subtotal' => $allocation['subtotal'],
                        'is_legacy_stock' => $allocation['is_legacy_stock'],
                    ]);
                }

                AuditLogger::record(
                    'stock_decremented',
                    $product->fresh(),
                    null,
                    [
                        'stock_quantity' => $product->fresh()->stock_quantity,
                        'batch_stock' => $product->fresh()->batchStockQuantity(),
                        'sale_id' => $sale->id,
                        'fifo_allocations' => count($line['allocations']),
                    ],
                    $businessId,
                    $user->id
                );
            }

            if ($isCreditSale && $creditCustomer) {
                $this->debtLedgerService->recordDebit(
                    $creditCustomer->fresh(),
                    $total,
                    $user,
                    $sale,
                    ($waiterMode ? 'Waiter tab' : 'Hardware credit sale') . ' #' . $saleNumber,
                    $invoiceDueAt
                );
            }

            AuditLogger::record(
                'sale_completed',
                $sale,
                null,
                $sale->toArray(),
                $businessId,
                $user->id
            );

            return $sale->load('items');
        });

        $sale->load('business.efrisSetting');

        if ($sale->business && $sale->business->usesEfris()) {
            app(EfrisService::class)->queueSaleSubmission($sale);
        }

        return $sale;
    }

    protected function resolveSaleBranchId(User $user, Collection $products): int
    {
        if ($user->branch_id) {
            return (int) $user->branch_id;
        }

        $branchIds = $products->pluck('branch_id')->filter()->unique()->values();

        if ($branchIds->count() !== 1) {
            throw ValidationException::withMessages([
                'items' => 'All products in one sale must belong to the same branch.',
            ]);
        }

        return (int) $branchIds->first();
    }

    protected function generateSaleNumber(int $businessId): string
    {
        Business::query()->whereKey($businessId)->lockForUpdate()->first();

        $count = Sale::query()
            ->withoutGlobalScope(BranchScope::class)
            ->withTrashed()
            ->where('business_id', $businessId)
            ->count() + 1;

        return 'SALE-' . str_pad((string) $businessId, 3, '0', STR_PAD_LEFT) . '-' . str_pad((string) $count, 6, '0', STR_PAD_LEFT);
    }
}
