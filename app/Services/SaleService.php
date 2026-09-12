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
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    protected DebtLedgerService $debtLedgerService;

    protected ProductBatchService $batchService;

    protected BranchResolver $branchResolver;

    public function __construct(
        DebtLedgerService $debtLedgerService,
        ProductBatchService $batchService,
        BranchResolver $branchResolver
    ) {
        $this->debtLedgerService = $debtLedgerService;
        $this->batchService = $batchService;
        $this->branchResolver = $branchResolver;
    }

    public function completeSale(User $user, array $payload): Sale
    {
        return DB::transaction(function () use ($user, $payload) {
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

                $quantity = (float) $item['quantity'];
                $available = $this->batchService->availableStock($product);

                if ($available < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => "Insufficient stock for {$product->displayName()}. Available: {$available}",
                    ]);
                }

                $deduction = $this->batchService->applyFifoDeduction($product, $quantity);
                $lineSubtotal = $deduction['subtotal'];
                $subtotal += $lineSubtotal;

                $lineItems[] = [
                    'product' => $product->fresh(),
                    'quantity' => $quantity,
                    'unit_price' => $deduction['unit_price'],
                    'cost_price' => $deduction['cost_price'],
                    'subtotal' => $lineSubtotal,
                    'allocations' => $deduction['allocations'],
                    'notes' => ! empty($item['notes']) ? mb_substr(trim((string) $item['notes']), 0, 500) : null,
                ];
            }

            $taxAmount = (float) ($payload['tax_amount'] ?? 0);
            $discountAmount = (float) ($payload['discount_amount'] ?? 0);
            $total = round($subtotal + $taxAmount - $discountAmount, 2);

            if ($isCreditSale && $customerId) {
                $customer = Customer::where('business_id', $businessId)
                    ->where('id', $customerId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (($customer->outstanding_balance + $total) > $customer->credit_limit) {
                    throw ValidationException::withMessages([
                        'customer_id' => 'Credit limit exceeded for this customer.',
                    ]);
                }
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
            ]);

            foreach ($lineItems as $line) {
                /** @var Product $product */
                $product = $line['product'];

                $saleItem = SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'product_name' => $product->displayName(),
                    'sku' => $product->sku,
                    'variant_attributes' => $product->attribute_values ?? $product->variant_attributes,
                    'measurement_unit' => $product->measurement_unit,
                    'quantity' => $line['quantity'],
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

            if ($isCreditSale && $customerId) {
                $this->debtLedgerService->recordDebit(
                    Customer::find($customerId),
                    $total,
                    $user,
                    $sale,
                    ($waiterMode ? 'Waiter tab' : 'Hardware credit sale') . ' #' . $saleNumber
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
