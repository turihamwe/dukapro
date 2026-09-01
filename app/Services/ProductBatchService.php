<?php

namespace App\Services;

use App\Helpers\AuditLogger;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductBatchService
{
    public function addBatch(Product $product, array $data, int $businessId, ?User $user = null): ProductBatch
    {
        if ($product->isVariableParent()) {
            throw ValidationException::withMessages([
                'quantity' => 'Add batches to individual variants, not the parent product.',
            ]);
        }

        if (! $product->is_sellable) {
            throw ValidationException::withMessages([
                'quantity' => 'This product is not sellable.',
            ]);
        }

        $quantity = (float) $data['quantity'];
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantity must be greater than zero.',
            ]);
        }

        $batch = ProductBatch::create([
            'business_id' => $businessId,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'remaining_quantity' => $quantity,
            'cost_price' => isset($data['cost_price']) ? (float) $data['cost_price'] : null,
            'selling_price' => (float) $data['selling_price'],
            'received_at' => isset($data['received_at'])
                ? Carbon::parse($data['received_at'])
                : Carbon::now(),
            'status' => ProductBatch::STATUS_ACTIVE,
            'notes' => $data['notes'] ?? null,
        ]);

        AuditLogger::record(
            'product_batch_created',
            $batch,
            null,
            $batch->toArray(),
            $businessId,
            $user ? $user->id : null
        );

        return $batch;
    }

    /**
     * FIFO allocation plan for deducting stock. Does not persist changes.
     *
     * @return array<int, array{product_batch_id: int|null, quantity: float, cost_price: float|null, selling_price: float, subtotal: float, is_legacy_stock: bool}>
     */
    public function planFifoDeduction(Product $product, float $quantity): array
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantity must be greater than zero.',
            ]);
        }

        $available = $this->availableStock($product);
        if ($available < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => "Insufficient stock for {$product->displayName()}. Available: {$available}",
            ]);
        }

        $remaining = $quantity;
        $allocations = [];

        $batches = ProductBatch::query()
            ->where('product_id', $product->id)
            ->active()
            ->orderBy('received_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $take = min((float) $batch->remaining_quantity, $remaining);
            if ($take <= 0) {
                continue;
            }

            $sellingPrice = (float) $batch->selling_price;
            $allocations[] = [
                'product_batch_id' => $batch->id,
                'quantity' => $take,
                'cost_price' => $batch->cost_price !== null ? (float) $batch->cost_price : null,
                'selling_price' => $sellingPrice,
                'subtotal' => round($take * $sellingPrice, 2),
                'is_legacy_stock' => false,
            ];

            $remaining -= $take;
        }

        if ($remaining > 0) {
            $legacyQty = (float) $product->stock_quantity;
            if ($legacyQty < $remaining) {
                throw ValidationException::withMessages([
                    'quantity' => "Insufficient stock for {$product->displayName()}. Available: {$available}",
                ]);
            }

            $sellingPrice = (float) $product->price;
            $allocations[] = [
                'product_batch_id' => null,
                'quantity' => $remaining,
                'cost_price' => $product->cost_price !== null ? (float) $product->cost_price : null,
                'selling_price' => $sellingPrice,
                'subtotal' => round($remaining * $sellingPrice, 2),
                'is_legacy_stock' => true,
            ];
        }

        return $allocations;
    }

    /**
     * Apply FIFO deduction and persist stock changes.
     *
     * @return array{allocations: array, unit_price: float, cost_price: float|null, subtotal: float}
     */
    public function applyFifoDeduction(Product $product, float $quantity): array
    {
        $allocations = $this->planFifoDeduction($product, $quantity);

        foreach ($allocations as $allocation) {
            if ($allocation['is_legacy_stock']) {
                $product->decrement('stock_quantity', $allocation['quantity']);
                continue;
            }

            $batch = ProductBatch::whereKey($allocation['product_batch_id'])->lockForUpdate()->firstOrFail();
            $batch->remaining_quantity = max(0, (float) $batch->remaining_quantity - $allocation['quantity']);
            $batch->markDepletedIfEmpty();
            if ($batch->isDirty()) {
                $batch->save();
            }
        }

        $subtotal = round(collect($allocations)->sum('subtotal'), 2);
        $totalQty = collect($allocations)->sum('quantity');
        $unitPrice = $totalQty > 0 ? round($subtotal / $totalQty, 2) : 0;

        $totalCost = 0;
        $costQty = 0;
        foreach ($allocations as $allocation) {
            if ($allocation['cost_price'] !== null) {
                $totalCost += $allocation['cost_price'] * $allocation['quantity'];
                $costQty += $allocation['quantity'];
            }
        }
        $costPrice = $costQty > 0 ? round($totalCost / $costQty, 2) : null;

        return [
            'allocations' => $allocations,
            'unit_price' => $unitPrice,
            'cost_price' => $costPrice,
            'subtotal' => $subtotal,
        ];
    }

    public function availableStock(Product $product): float
    {
        if ($product->relationLoaded('activeBatches')) {
            $batchStock = (float) $product->activeBatches->sum('remaining_quantity');
        } else {
            $batchStock = (float) ProductBatch::query()
                ->where('product_id', $product->id)
                ->active()
                ->sum('remaining_quantity');
        }

        return round((float) $product->stock_quantity + $batchStock, 3);
    }

    public function fifoSellingPrice(Product $product): float
    {
        $oldestBatch = ProductBatch::query()
            ->where('product_id', $product->id)
            ->active()
            ->orderBy('received_at')
            ->orderBy('id')
            ->first();

        if ($oldestBatch) {
            return (float) $oldestBatch->selling_price;
        }

        return (float) $product->price;
    }

    public function hasActiveBatches(Product $product): bool
    {
        return ProductBatch::query()
            ->where('product_id', $product->id)
            ->active()
            ->exists();
    }

    public function activeBatchCount(Product $product): int
    {
        return ProductBatch::query()
            ->where('product_id', $product->id)
            ->active()
            ->count();
    }
}
