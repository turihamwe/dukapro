<?php

namespace App\Services;

use App\Helpers\AuditLogger;
use App\Models\Brand;
use App\Models\Business;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductInventoryService
{
    public function createSimple(array $data, int $businessId): Product
    {
        $data['sku'] = $this->resolveSku($data['sku'] ?? null, $data['name'], $businessId);

        return Product::create(array_merge($data, [
            'business_id' => $businessId,
            'is_active' => true,
            'is_sellable' => true,
            'parent_id' => null,
        ]));
    }

    public function createWithVariants(array $parentData, array $variants, int $businessId): Product
    {
        if (empty($variants)) {
            throw ValidationException::withMessages([
                'variants' => 'Add at least one product variant.',
            ]);
        }

        return DB::transaction(function () use ($parentData, $variants, $businessId) {
            $parent = Product::create(array_merge($parentData, [
                'business_id' => $businessId,
                'is_active' => true,
                'is_sellable' => false,
                'price' => 0,
                'stock_quantity' => 0,
                'sku' => null,
                'parent_id' => null,
            ]));

            foreach ($variants as $index => $variant) {
                $this->createVariantChild($parent, $variant, $businessId, $index);
            }

            AuditLogger::record('product_created', $parent, null, $parent->fresh(['variants'])->toArray());

            return $parent->fresh(['variants', 'brand']);
        });
    }

    public function updateSimple(Product $product, array $data): Product
    {
        if ($product->isVariableParent()) {
            throw ValidationException::withMessages([
                'name' => 'This product has variants. Edit variants from the product detail page.',
            ]);
        }

        if ($product->parent_id !== null) {
            throw ValidationException::withMessages([
                'name' => 'Edit this variant from its parent product.',
            ]);
        }

        $old = $product->toArray();
        unset($data['sku']);
        $product->update($data);
        AuditLogger::record('product_updated', $product, $old, $product->fresh()->toArray());

        return $product->fresh();
    }

    public function updateVariableParent(Product $product, array $parentData, array $variants): Product
    {
        if ($product->parent_id !== null) {
            throw ValidationException::withMessages([
                'name' => 'Cannot update a variant row as a parent product.',
            ]);
        }

        return DB::transaction(function () use ($product, $parentData, $variants) {
            $old = $product->toArray();
            $product->update(array_merge($parentData, [
                'is_sellable' => false,
                'price' => 0,
                'stock_quantity' => 0,
            ]));

            $existingIds = collect($variants)->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();
            $product->variants()->whereNotIn('id', $existingIds)->delete();

            foreach ($variants as $index => $variant) {
                if (! empty($variant['id'])) {
                    $child = $product->variants()->whereKey($variant['id'])->firstOrFail();
                    $child->update($this->variantPayload($product, $variant, $index, (int) $variant['id']));
                    continue;
                }

                $this->createVariantChild($product, $variant, (int) $product->business_id, $index);
            }

            AuditLogger::record('product_updated', $product, $old, $product->fresh(['variants'])->toArray());

            return $product->fresh(['variants', 'brand']);
        });
    }

    public function syncVariantChildren(Product $parent, array $variants): void
    {
        if (! $parent->isVariableParent() && empty($variants)) {
            return;
        }

        $this->updateVariableParent($parent, $parent->only([
            'name', 'brand_id', 'description', 'measurement_unit', 'critical_threshold', 'is_active',
        ]), $variants);
    }

    protected function createVariantChild(Product $parent, array $variant, int $businessId, int $index): Product
    {
        return Product::create(array_merge($this->variantPayload($parent, $variant, $index), [
            'business_id' => $businessId,
            'parent_id' => $parent->id,
            'brand_id' => $parent->brand_id,
            'name' => $parent->name,
            'description' => $parent->description,
            'measurement_unit' => $parent->measurement_unit,
            'critical_threshold' => $parent->critical_threshold,
            'is_active' => $parent->is_active,
            'is_sellable' => true,
        ]));
    }

    protected function variantPayload(Product $parent, array $variant, int $index, ?int $ignoreProductId = null): array
    {
        $attributes = $variant['attribute_values'] ?? [];
        if (! is_array($attributes)) {
            $attributes = [];
        }

        ksort($attributes);

        return [
            'attribute_values' => $attributes,
            'variant_attributes' => $attributes,
            'price' => (float) ($variant['price'] ?? 0),
            'cost_price' => isset($variant['cost_price']) ? (float) $variant['cost_price'] : null,
            'stock_quantity' => (float) ($variant['stock_quantity'] ?? 0),
            'sku' => $this->normalizeSku($variant['sku'] ?? null, $parent, $attributes, $index, (int) $parent->business_id, $ignoreProductId),
        ];
    }

    protected function normalizeSku(?string $sku, Product $parent, array $attributes, int $index, int $businessId, ?int $ignoreProductId = null): string
    {
        if ($ignoreProductId) {
            $existing = Product::find($ignoreProductId);
            if ($existing && $existing->sku) {
                return $existing->sku;
            }
        }

        return $this->nextSequenceSku($businessId, $ignoreProductId);
    }

    protected function resolveSku(?string $sku, string $name, int $businessId, ?int $ignoreProductId = null): string
    {
        if ($ignoreProductId) {
            $existing = Product::find($ignoreProductId);
            if ($existing && $existing->sku) {
                return $existing->sku;
            }
        }

        return $this->nextSequenceSku($businessId, $ignoreProductId);
    }

    protected function businessSkuPrefix(int $businessId): string
    {
        $business = Business::find($businessId);
        $name = $business ? $business->name : 'ITEM';
        $letters = preg_replace('/[^A-Za-z]/', '', $name) ?: 'ITEM';
        $prefix = strtoupper(substr($letters, 0, 3));

        return str_pad($prefix, 3, 'X');
    }

    protected function nextSequenceSku(int $businessId, ?int $ignoreProductId = null): string
    {
        $prefix = $this->businessSkuPrefix($businessId);
        $pattern = $prefix . '-%';

        $existingSkus = Product::query()
            ->where('business_id', $businessId)
            ->where('sku', 'like', $pattern)
            ->when($ignoreProductId, fn ($query) => $query->where('id', '!=', $ignoreProductId))
            ->pluck('sku');

        $max = 0;
        foreach ($existingSkus as $existingSku) {
            if (preg_match('/^' . preg_quote($prefix, '/') . '-(\d+)$/', $existingSku, $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }

        $sku = sprintf('%s-%03d', $prefix, $max + 1);

        return $this->uniqueSku($sku, $businessId, $ignoreProductId);
    }

    protected function uniqueSku(string $base, int $businessId, ?int $ignoreProductId = null): string
    {
        $sku = $base;
        $counter = 1;

        while (Product::query()
            ->where('business_id', $businessId)
            ->where('sku', $sku)
            ->when($ignoreProductId, fn ($query) => $query->where('id', '!=', $ignoreProductId))
            ->exists()) {
            $sku = $base . '-' . $counter++;
        }

        return $sku;
    }

    public static function cartesianCombinations(array $attributeSelections): array
    {
        if (empty($attributeSelections)) {
            return [];
        }

        $results = [[]];

        foreach ($attributeSelections as $attributeName => $values) {
            $values = array_values(array_filter((array) $values, fn ($value) => trim((string) $value) !== ''));
            if (empty($values)) {
                continue;
            }

            $append = [];
            foreach ($results as $result) {
                foreach ($values as $value) {
                    $combo = $result;
                    $combo[$attributeName] = $value;
                    $append[] = $combo;
                }
            }
            $results = $append;
        }

        return array_values(array_filter($results));
    }
}
