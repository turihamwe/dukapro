<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use App\Support\AnalyticsDateRange;
use Illuminate\Support\Collection;

class LowStockAlertService
{
    protected ProductBatchService $batchService;

    public function __construct(ProductBatchService $batchService)
    {
        $this->batchService = $batchService;
    }

    public function lowStockProducts(Business $business, ?User $user = null, int $limit = 12): Collection
    {
        $products = Product::query()
            ->catalog()
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->with(['variants', 'branch:id,name'])
            ->orderBy('name')
            ->get();

        return $products->filter(function (Product $product) {
            $available = $product->isVariableParent()
                ? $product->variants->sum(fn (Product $variant) => $this->batchService->availableStock($variant))
                : $this->batchService->availableStock($product);
            $threshold = $product->critical_threshold ?? AnalyticsDateRange::LOW_STOCK_THRESHOLD;

            return $available <= $threshold;
        })->take($limit)->map(function (Product $product) use ($business) {
            $available = $product->isVariableParent()
                ? $product->variants->sum(fn (Product $variant) => $this->batchService->availableStock($variant))
                : $this->batchService->availableStock($product);

            return [
                'id' => $product->id,
                'name' => $product->name,
                'available' => $available,
                'threshold' => $product->critical_threshold ?? AnalyticsDateRange::LOW_STOCK_THRESHOLD,
                'unit' => $product->measurement_unit,
                'branch_id' => $product->branch_id,
                'branch_name' => $product->branch->name ?? null,
            ];
        })->values();
    }

    public function count(Business $business): int
    {
        return $this->lowStockProducts($business, null, 999)->count();
    }
}
