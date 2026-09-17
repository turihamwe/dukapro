<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ProductUnitService
{
    public function ensureBaseUnit(Product $product): ProductUnit
    {
        $product->loadMissing('units');

        $base = $product->units->firstWhere('is_base_unit', true);

        if ($base) {
            $base->update([
                'unit_name' => $product->measurement_unit,
                'conversion_factor' => 1,
                'is_base_unit' => true,
                'price' => $product->price,
                'sort_order' => 0,
            ]);

            return $base->fresh();
        }

        return ProductUnit::create([
            'product_id' => $product->id,
            'unit_name' => $product->measurement_unit,
            'conversion_factor' => 1,
            'is_base_unit' => true,
            'price' => $product->price,
            'sort_order' => 0,
        ]);
    }

    public function syncSecondaryUnits(Product $product, array $secondaryUnits): void
    {
        $this->ensureBaseUnit($product->fresh());

        $normalized = [];
        $sort = 1;

        foreach ($secondaryUnits as $row) {
            $name = strtolower(trim((string) ($row['unit_name'] ?? '')));
            $factor = (float) ($row['conversion_factor'] ?? 0);
            $price = isset($row['price']) && $row['price'] !== '' ? (float) $row['price'] : null;

            if ($name === '' || $factor <= 0) {
                continue;
            }

            if ($name === strtolower((string) $product->measurement_unit)) {
                throw ValidationException::withMessages([
                    'product_units' => 'Secondary unit "' . $name . '" cannot use the same name as the base unit.',
                ]);
            }

            $normalized[] = [
                'unit_name' => $name,
                'conversion_factor' => round($factor, 6),
                'price' => $price !== null && $price >= 0 ? round($price, 2) : null,
                'sort_order' => $sort++,
            ];
        }

        $keepNames = collect($normalized)->pluck('unit_name')->all();

        $product->units()
            ->where('is_base_unit', false)
            ->whereNotIn('unit_name', $keepNames)
            ->delete();

        foreach ($normalized as $row) {
            $product->units()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'unit_name' => $row['unit_name'],
                ],
                [
                    'conversion_factor' => $row['conversion_factor'],
                    'is_base_unit' => false,
                    'price' => $row['price'],
                    'sort_order' => $row['sort_order'],
                ]
            );
        }
    }

    public function unitsForSale(Product $product): Collection
    {
        $product->loadMissing('units');

        if ($product->units->isEmpty()) {
            $this->ensureBaseUnit($product);
            $product->load('units');
        }

        return $product->units->sortBy('sort_order')->values();
    }

    public function resolveUnit(Product $product, ?int $productUnitId): ProductUnit
    {
        $units = $this->unitsForSale($product);

        if ($productUnitId) {
            $unit = $units->firstWhere('id', $productUnitId);

            if (! $unit) {
                throw ValidationException::withMessages([
                    'items' => 'Selected unit is invalid for ' . $product->displayName() . '.',
                ]);
            }

            return $unit;
        }

        $base = $units->firstWhere('is_base_unit', true) ?? $units->first();

        if (! $base) {
            throw ValidationException::withMessages([
                'items' => 'No sellable unit configured for ' . $product->displayName() . '.',
            ]);
        }

        return $base;
    }

    public function toBaseQuantity(ProductUnit $unit, float $quantity): float
    {
        return $unit->toBaseQuantity($quantity);
    }

    public function posCatalogUnits(Product $product): array
    {
        return $this->unitsForSale($product)->map(function (ProductUnit $unit) use ($product) {
            return [
                'id' => $unit->id,
                'name' => $unit->unit_name,
                'factor' => (float) $unit->conversion_factor,
                'price' => round($unit->sellingPrice($product), 2),
                'is_base' => (bool) $unit->is_base_unit,
            ];
        })->values()->all();
    }
}
