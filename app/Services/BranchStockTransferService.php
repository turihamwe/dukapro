<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\BranchStockTransfer;
use App\Models\Product;
use App\Models\User;
use App\Scopes\BranchScope;
use App\Support\BatchMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BranchStockTransferService
{
    public const DESTINATION_MATCH_MESSAGE = 'No matching product exists at the destination branch. Add the same product there first (matching name/SKU and variant).';

    protected ProductBatchService $batchService;

    protected ProductInventoryService $inventoryService;

    public function __construct(ProductBatchService $batchService, ProductInventoryService $inventoryService)
    {
        $this->batchService = $batchService;
        $this->inventoryService = $inventoryService;
    }

    /**
     * @param  array{from_branch_id: int, to_branch_id: int, product_id: int, variant_id?: int|null, quantity: float}  $data
     */
    public function transfer(User $user, array $data): BranchStockTransfer
    {
        $business = $user->business;
        $fromBranchId = (int) $data['from_branch_id'];
        $toBranchId = (int) $data['to_branch_id'];
        $quantity = (float) $data['quantity'];

        if ($fromBranchId === $toBranchId) {
            throw ValidationException::withMessages([
                'to_branch_id' => 'Choose a different destination branch.',
            ]);
        }

        $this->assertBranchBelongsToBusiness($fromBranchId, (int) $business->id);
        $this->assertBranchBelongsToBusiness($toBranchId, (int) $business->id);

        if ($user->branch_id && (int) $user->branch_id !== $fromBranchId) {
            throw ValidationException::withMessages([
                'from_branch_id' => 'You can only transfer stock from your assigned branch.',
            ]);
        }

        $catalogProduct = Product::query()
            ->withoutGlobalScope(BranchScope::class)
            ->where('business_id', $business->id)
            ->whereNull('parent_id')
            ->findOrFail($data['product_id']);

        if ((int) $catalogProduct->branch_id !== $fromBranchId) {
            throw ValidationException::withMessages([
                'product_id' => 'Selected product is not in the source branch.',
            ]);
        }

        if ($catalogProduct->isService()) {
            throw ValidationException::withMessages([
                'product_id' => 'Services cannot be transferred between branches.',
            ]);
        }

        $source = $catalogProduct;
        if ($catalogProduct->isVariableParent()) {
            if (empty($data['variant_id'])) {
                throw ValidationException::withMessages([
                    'variant_id' => 'Select a variant to transfer.',
                ]);
            }
            $source = $catalogProduct->variants()->whereKey($data['variant_id'])->firstOrFail();
        } elseif (! empty($data['variant_id'])) {
            throw ValidationException::withMessages([
                'variant_id' => 'This product has no variants.',
            ]);
        }

        $destination = $this->resolveCounterpart($source, $toBranchId);
        if (! $destination) {
            throw ValidationException::withMessages([
                'to_branch_id' => self::DESTINATION_MATCH_MESSAGE,
            ]);
        }

        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Enter a quantity greater than zero.',
            ]);
        }

        $available = $this->batchService->availableStock($source);
        if ($quantity > $available + 0.0001) {
            throw ValidationException::withMessages([
                'quantity' => 'Insufficient stock at the source branch. Available: ' . $available,
            ]);
        }

        return DB::transaction(function () use ($user, $business, $fromBranchId, $toBranchId, $source, $destination, $quantity) {
            $source = Product::query()->withoutGlobalScope(BranchScope::class)->lockForUpdate()->findOrFail($source->id);
            $destination = Product::query()->withoutGlobalScope(BranchScope::class)->lockForUpdate()->findOrFail($destination->id);

            $available = $this->batchService->availableStock($source);
            if ($quantity > $available + 0.0001) {
                throw ValidationException::withMessages([
                    'quantity' => 'Insufficient stock at the source branch. Available: ' . $available,
                ]);
            }

            if (BatchMode::active($business, $fromBranchId) || $this->batchService->hasActiveBatches($source)) {
                $this->batchService->applyFifoDeduction($source, $quantity);
            } else {
                $source->decrement('stock_quantity', $quantity);
            }

            $this->inventoryService->topUpStock($destination, $quantity);

            $transfer = BranchStockTransfer::create([
                'business_id' => $business->id,
                'from_branch_id' => $fromBranchId,
                'to_branch_id' => $toBranchId,
                'from_product_id' => $source->id,
                'to_product_id' => $destination->id,
                'user_id' => $user->id,
                'quantity' => $quantity,
            ]);

            return $transfer->load(['fromBranch', 'toBranch', 'fromProduct', 'toProduct']);
        });
    }

    public function resolveCounterpart(Product $source, int $toBranchId): ?Product
    {
        if ($source->parent_id) {
            $parent = $source->parent ?? Product::query()->find($source->parent_id);
            if (! $parent) {
                return null;
            }
            $destParent = $this->findCatalogMatch($parent, $toBranchId);
            if (! $destParent) {
                return null;
            }

            $sourceKey = ProductInventoryService::variantCombinationKey($source->attribute_values ?? []);

            return $destParent->variants()
                ->get()
                ->first(function (Product $variant) use ($sourceKey) {
                    return ProductInventoryService::variantCombinationKey($variant->attribute_values ?? []) === $sourceKey;
                });
        }

        return $this->findCatalogMatch($source, $toBranchId);
    }

    protected function findCatalogMatch(Product $source, int $toBranchId): ?Product
    {
        $base = Product::query()
            ->withoutGlobalScope(BranchScope::class)
            ->where('business_id', $source->business_id)
            ->where('branch_id', $toBranchId)
            ->whereNull('parent_id');

        if (filled($source->sku)) {
            $bySku = (clone $base)->where('sku', $source->sku)->first();
            if ($bySku) {
                return $bySku;
            }
        }

        return (clone $base)->where('name', $source->name)->first();
    }

    protected function assertBranchBelongsToBusiness(int $branchId, int $businessId): void
    {
        $exists = Branch::query()
            ->where('business_id', $businessId)
            ->where('is_active', true)
            ->whereKey($branchId)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'from_branch_id' => 'Invalid branch selected.',
            ]);
        }
    }
}
