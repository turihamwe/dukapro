<?php

namespace App\Support;

use App\Models\Product;

class ProductInventoryValuation
{
    public static function purchaseDefaultUnitCost(Product $product): float
    {
        if ($product->default_cost_price !== null && (float) $product->default_cost_price > 0) {
            return (float) $product->default_cost_price;
        }

        if ($product->cost_price !== null && (float) $product->cost_price > 0) {
            return (float) $product->cost_price;
        }

        return (float) ($product->price ?? 0);
    }

    public static function lastPurchaseUnitCost(Product $product): ?float
    {
        if ($product->cost_price === null || (float) $product->cost_price <= 0) {
            return null;
        }

        return (float) $product->cost_price;
    }

    public static function percentChangeFromDefault(?float $last, ?float $default): ?float
    {
        if ($last === null || $default === null || $default <= 0) {
            return null;
        }

        return round((($last - $default) / $default) * 100, 1);
    }

    public static function valuationUnitCost(Product $product): float
    {
        if ($product->inventory_cost_price !== null && (float) $product->inventory_cost_price > 0) {
            return (float) $product->inventory_cost_price;
        }

        if ($product->default_cost_price !== null && (float) $product->default_cost_price > 0) {
            return (float) $product->default_cost_price;
        }

        return (float) ($product->cost_price ?? 0);
    }

    public static function inventoryValue(Product $product): float
    {
        $value = (float) $product->stock_quantity * self::valuationUnitCost($product);

        if ($product->hasActiveBatches()) {
            $batches = $product->relationLoaded('activeBatches')
                ? $product->activeBatches
                : $product->activeBatches()->get(['remaining_quantity', 'cost_price']);

            $fallback = self::valuationUnitCost($product);

            foreach ($batches as $batch) {
                $unit = (float) ($batch->cost_price ?? $fallback);
                $value += (float) $batch->remaining_quantity * $unit;
            }
        }

        return round($value, 2);
    }

    public static function weightedAverageAfterReceive(Product $product, float $receiveQty, float $unitCost): float
    {
        $oldQty = (float) $product->stock_quantity;
        $oldAvg = self::valuationUnitCost($product);
        $newQty = $oldQty + $receiveQty;

        if ($newQty <= 0) {
            return round($unitCost, 2);
        }

        return round(($oldQty * $oldAvg + $receiveQty * $unitCost) / $newQty, 2);
    }
}
