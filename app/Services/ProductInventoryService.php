<?php

namespace App\Services;

use App\Helpers\AuditLogger;
use App\Models\Brand;
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
        if (array_key_exists('sku', $data)) {
            $data['sku'] = $this->resolveSku($data['sku'] ?? null, $data['name'] ?? $product->name, (int) $product->business_id, $product->id);
        }
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
        $sku = trim((string) $sku);
        if ($sku !== '') {
            return $sku;
        }

        $suffix = collect($attributes)->map(fn ($value) => Str::slug((string) $value))->filter()->implode('-');
        $base = $suffix !== '' ? Str::upper(Str::slug($parent->name) . '-' . $suffix) : Str::upper(Str::slug($parent->name) . '-' . ($index + 1));

        return $this->uniqueSku($base, $businessId, $ignoreProductId);
    }

    protected function resolveSku(?string $sku, string $name, int $businessId, ?int $ignoreProductId = null): string
    {
        $sku = trim((string) $sku);
        if ($sku !== '') {
            return $sku;
        }

        $base = Str::upper(Str::slug($name)) ?: 'ITEM';
        $base = Str::limit($base, 40, '');

        return $this->uniqueSku($base, $businessId, $ignoreProductId);
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
