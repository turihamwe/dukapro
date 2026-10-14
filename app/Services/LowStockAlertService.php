<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use App\Support\AnalyticsDateRange;
use App\Support\LowStockAlertMode;
use Illuminate\Support\Collection;

class LowStockAlertService
{
    protected ProductBatchService $batchService;

    public function __construct(ProductBatchService $batchService)
    {
        $this->batchService = $batchService;
    }

    public function availableStock(Product $product): float
    {
        return $this->batchService->availableStock($product);
    }

    public function thresholdFor(Product $product, ?Product $parent = null): int
    {
        if ($product->critical_threshold !== null && $product->critical_threshold !== '') {
            return (int) $product->critical_threshold;
        }

        if ($parent && $parent->critical_threshold !== null && $parent->critical_threshold !== '') {
            return (int) $parent->critical_threshold;
        }

        return AnalyticsDateRange::LOW_STOCK_THRESHOLD;
    }

    /**
     * Sellable quantity for low-stock checks on a catalog parent (combined) or simple product.
     */
    public function catalogAvailableStock(Product $product): float
    {
        if ($product->parent_id !== null) {
            return $this->availableStock($product);
        }

        if ($product->isVariableParent()) {
            $product->loadMissing('variants');

            return round(
                (float) $product->variants->sum(fn (Product $variant) => $this->availableStock($variant)),
                3
            );
        }

        return $this->availableStock($product);
    }

    public function isCatalogProductLowStock(Product $product, Business $business): bool
    {
        if ($product->parent_id !== null || $product->isService()) {
            return false;
        }

        if (LowStockAlertMode::isPerVariant($business) && $product->isVariableParent()) {
            $product->loadMissing('variants');

            foreach ($product->variants as $variant) {
                if ($this->availableStock($variant) <= $this->thresholdFor($variant, $product)) {
                    return true;
                }
            }

            return false;
        }

        $available = $this->catalogAvailableStock($product);

        return $available <= $this->thresholdFor($product);
    }

    /**
     * @return list<array{id: int, name: string, available: float, threshold: int, unit: string, branch_id: ?int, branch_name: ?string, parent_id: ?int}>
     */
    public function alertsForCatalogProduct(Product $product, Business $business): array
    {
        if ($product->parent_id !== null || $product->isService()) {
            return [];
        }

        $rows = [];

        if (LowStockAlertMode::isPerVariant($business) && $product->isVariableParent()) {
            $product->loadMissing('variants');

            foreach ($product->variants as $variant) {
                $available = $this->availableStock($variant);
                $threshold = $this->thresholdFor($variant, $product);

                if ($available > $threshold) {
                    continue;
                }

                $rows[] = $this->formatAlertRow($product, $business, $available, $threshold, $variant);
            }

            return $rows;
        }

        $available = $this->catalogAvailableStock($product);
        $threshold = $this->thresholdFor($product);

        if ($available <= $threshold) {
            $rows[] = $this->formatAlertRow($product, $business, $available, $threshold, null);
        }

        return $rows;
    }

    public function allAlerts(Business $business, ?User $user = null, int $limit = 12): Collection
    {
        $products = $this->catalogProductsQuery($business)->get();
        $alerts = collect();

        foreach ($products as $product) {
            foreach ($this->alertsForCatalogProduct($product, $business) as $row) {
                $alerts->push($row);
            }
        }

        return $alerts->sortBy('name')->values()->take($limit);
    }

    public function lowStockProducts(Business $business, ?User $user = null, int $limit = 12): Collection
    {
        return $this->allAlerts($business, $user, $limit);
    }

    public function count(Business $business, ?User $user = null): int
    {
        return $this->allAlerts($business, $user, 10000)->count();
    }

    protected function catalogProductsQuery(Business $business)
    {
        return Product::query()
            ->catalog()
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->with(['variants', 'branch:id,name'])
            ->orderBy('name');
    }

    /**
     * @return array{id: int, name: string, available: float, threshold: int, unit: string, branch_id: ?int, branch_name: ?string, parent_id: ?int}
     */
    protected function formatAlertRow(
        Product $catalogProduct,
        Business $business,
        float $available,
        int $threshold,
        ?Product $variant
    ): array {
        $topUpProduct = $variant ?? $catalogProduct;
        $name = $catalogProduct->name;

        if ($variant) {
            $label = $variant->displayName();
            if ($label !== $catalogProduct->name) {
                $name = $label;
            } else {
                $name = $catalogProduct->name . ' · ' . ($variant->sku ?? 'Variant #' . $variant->id);
            }
        }

        return [
            'id' => $topUpProduct->id,
            'name' => $name,
            'available' => $available,
            'threshold' => $threshold,
            'unit' => $topUpProduct->measurement_unit ?: $catalogProduct->measurement_unit,
            'branch_id' => $catalogProduct->branch_id,
            'branch_name' => $catalogProduct->branch->name ?? null,
            'parent_id' => $variant ? $catalogProduct->id : null,
        ];
    }
}
