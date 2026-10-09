<?php

namespace App\Services;

use App\Helpers\AuditLogger;
use App\Models\Brand;
use App\Models\Business;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductInventoryService
{
    protected ProductUnitService $unitService;

    public function __construct(ProductUnitService $unitService)
    {
        $this->unitService = $unitService;
    }

    public function createSimple(array $data, int $businessId, array $secondaryUnits = []): Product
    {
        $providedSku = isset($data['sku']) ? trim((string) $data['sku']) : '';
        $attempts = 0;

        while ($attempts < 3) {
            $payload = $data;
            $payload['sku'] = $this->resolveSku(
                $attempts > 0 && $providedSku === '' ? null : ($data['sku'] ?? null),
                $data['name'],
                $businessId
            );

            try {
                $product = Product::create(array_merge($payload, [
                    'business_id' => $businessId,
                    'is_active' => true,
                    'is_sellable' => array_key_exists('is_sellable', $payload)
                        ? (bool) $payload['is_sellable']
                        : true,
                    'parent_id' => null,
                ]));

                $this->unitService->ensureBaseUnit($product);
                $this->unitService->syncSecondaryUnits($product, $secondaryUnits);

                return $product->fresh(['units']);
            } catch (QueryException $exception) {
                if (! $this->isDuplicateSkuException($exception) || $providedSku !== '' || ++$attempts >= 3) {
                    if ($this->isDuplicateSkuException($exception) && $providedSku !== '') {
                        throw ValidationException::withMessages([
                            'sku' => 'This SKU is already used by another product in your catalog.',
                        ]);
                    }

                    throw $exception;
                }
            }
        }

        throw ValidationException::withMessages([
            'sku' => 'Could not assign a unique SKU. Try again or enter a different SKU.',
        ]);
    }

    public function createWithVariants(array $parentData, array $variants, int $businessId): Product
    {
        if (empty($variants)) {
            throw ValidationException::withMessages([
                'variants' => 'Add at least one product variant.',
            ]);
        }

        $this->assertUniqueVariantCombinations($variants);

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
                $this->upsertVariantChild($parent, $variant, $businessId, $index);
            }

            AuditLogger::record('product_created', $parent, null, $parent->fresh(['variants'])->toArray());

            return $parent->fresh(['variants', 'brand']);
        });
    }

    public function updateSimple(Product $product, array $data, array $secondaryUnits = []): Product
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
        $this->unitService->ensureBaseUnit($product->fresh());
        $this->unitService->syncSecondaryUnits($product->fresh(), $secondaryUnits);
        AuditLogger::record('product_updated', $product, $old, $product->fresh()->toArray());

        return $product->fresh(['units']);
    }

    public function updateVariableParent(Product $product, array $parentData, array $variants, array $deletedVariantIds = []): Product
    {
        if ($product->parent_id !== null) {
            throw ValidationException::withMessages([
                'name' => 'Cannot update a variant row as a parent product.',
            ]);
        }

        $product->loadMissing('variants');

        return DB::transaction(function () use ($product, $parentData, $variants, $deletedVariantIds) {
            $old = $product->toArray();
            $product->update(array_merge($parentData, [
                'is_sellable' => false,
                'price' => 0,
                'stock_quantity' => 0,
            ]));

            if ($product->wasChanged('branch_id')) {
                $product->variants()->update(['branch_id' => $product->branch_id]);
            }

            $allowedVariantIds = $product->variants()->pluck('id')->map(fn ($id) => (int) $id)->all();
            $explicitDeletes = collect($deletedVariantIds)
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => in_array($id, $allowedVariantIds, true))
                ->unique()
                ->values()
                ->all();

            $this->assertUniqueVariantCombinations($variants, $product, $explicitDeletes);

            if ($explicitDeletes !== []) {
                $product->variants()->whereIn('id', $explicitDeletes)->get()->each(function (Product $child) {
                    $old = $child->toArray();
                    $child->delete();
                    AuditLogger::record('product_deleted', $child, $old, null);
                });
            }

            foreach ($variants as $index => $variant) {
                if (! empty($variant['id'])) {
                    $child = $product->variants()->whereKey($variant['id'])->firstOrFail();
                    $child->update($this->variantBaselinePayload($product, $variant, $index, (int) $variant['id']));
                    continue;
                }

                $this->upsertVariantChild($product, $variant, (int) $product->business_id, $index);
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

    protected function upsertVariantChild(Product $parent, array $variant, int $businessId, int $index): Product
    {
        $combinationKey = self::variantCombinationKey($variant['attribute_values'] ?? []);
        if ($combinationKey !== '') {
            $trashed = Product::onlyTrashed()
                ->where('parent_id', $parent->id)
                ->where('business_id', $businessId)
                ->get()
                ->first(function (Product $child) use ($combinationKey) {
                    return self::variantCombinationKey($child->attribute_values ?? []) === $combinationKey;
                });

            if ($trashed) {
                $trashed->restore();
                $trashed->update($this->variantBaselinePayload($parent, $variant, $index, (int) $trashed->id));

                return $trashed->fresh();
            }
        }

        return $this->createVariantChild($parent, $variant, $businessId, $index);
    }

    protected function createVariantChild(Product $parent, array $variant, int $businessId, int $index): Product
    {
        return Product::create(array_merge($this->variantPayload($parent, $variant, $index), [
            'business_id' => $businessId,
            'branch_id' => $parent->branch_id,
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

    protected function variantBaselinePayload(Product $parent, array $variant, int $index, ?int $ignoreProductId = null): array
    {
        $payload = $this->variantPayload($parent, $variant, $index, $ignoreProductId);

        if (array_key_exists('cost_price', $payload)) {
            $payload['default_cost_price'] = $payload['cost_price'];
            unset($payload['cost_price'], $payload['inventory_cost_price']);
        }

        return $payload;
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
            'default_cost_price' => isset($variant['cost_price']) ? (float) $variant['cost_price'] : null,
            'inventory_cost_price' => isset($variant['cost_price']) ? (float) $variant['cost_price'] : null,
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

        $provided = strtoupper(trim((string) $sku));
        if ($provided !== '') {
            if ($this->skuExists($provided, $businessId, $ignoreProductId)) {
                throw ValidationException::withMessages([
                    'sku' => 'This SKU is already used by another product in your catalog.',
                ]);
            }

            return $provided;
        }

        return $this->nextSequenceSku($businessId, $ignoreProductId);
    }

    protected function skuExists(string $sku, int $businessId, ?int $ignoreProductId = null): bool
    {
        return Product::withTrashed()
            ->where('business_id', $businessId)
            ->where('sku', $sku)
            ->when($ignoreProductId, fn ($query) => $query->where('id', '!=', $ignoreProductId))
            ->exists();
    }

    protected function isDuplicateSkuException(QueryException $exception): bool
    {
        $errorCode = (int) ($exception->errorInfo[1] ?? 0);

        if ($errorCode !== 1062) {
            return false;
        }

        $message = $exception->getMessage();

        return str_contains($message, 'products_business_id_sku_unique')
            || str_contains($message, 'Duplicate entry');
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

        $existingSkus = Product::withTrashed()
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

        while ($this->skuExists($sku, $businessId, $ignoreProductId)) {
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

    public function topUpStock(Product $product, float $quantity): Product
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Enter a quantity greater than zero.',
            ]);
        }

        if ($product->isVariableParent()) {
            throw ValidationException::withMessages([
                'quantity' => 'Top up individual variants, not the parent product.',
            ]);
        }

        $product->increment('stock_quantity', $quantity);

        return $product->fresh();
    }

    /**
     * @param  array<int, array<string, mixed>>  $variants
     * @param  array<int, int>  $deletedVariantIds
     */
    public function assertUniqueVariantCombinations(array $variants, ?Product $parent = null, array $deletedVariantIds = []): void
    {
        $seen = [];

        foreach ($variants as $index => $variant) {
            $key = self::variantCombinationKey($variant['attribute_values'] ?? []);
            if ($key === '') {
                continue;
            }
            if (isset($seen[$key])) {
                throw ValidationException::withMessages([
                    'variants' => 'Duplicate variant in this form: '.$this->describeVariantCombination($variant['attribute_values'] ?? []),
                ]);
            }
            $seen[$key] = $index;
        }

        if ($parent === null) {
            return;
        }

        $deleted = array_flip(array_map('intval', $deletedVariantIds));

        foreach ($variants as $variant) {
            if (! empty($variant['id'])) {
                continue;
            }

            $key = self::variantCombinationKey($variant['attribute_values'] ?? []);
            if ($key === '') {
                continue;
            }

            foreach ($parent->variants as $child) {
                if (isset($deleted[(int) $child->id])) {
                    continue;
                }

                if (self::variantCombinationKey($child->attribute_values ?? []) === $key) {
                    throw ValidationException::withMessages([
                        'variants' => 'This variant already exists for this product: '.$this->describeVariantCombination($variant['attribute_values'] ?? []),
                    ]);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $attributeValues
     */
    public static function variantCombinationKey(array $attributeValues): string
    {
        if ($attributeValues === []) {
            return '';
        }

        $normalized = [];
        foreach ($attributeValues as $name => $value) {
            $normalized[(string) $name] = (string) $value;
        }
        ksort($normalized);

        return json_encode($normalized, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param  array<string, mixed>  $attributeValues
     */
    protected function describeVariantCombination(array $attributeValues): string
    {
        if ($attributeValues === []) {
            return 'unknown options';
        }

        $parts = [];
        foreach ($attributeValues as $name => $value) {
            $parts[] = $name.': '.$value;
        }
        sort($parts);

        return implode(', ', $parts);
    }
}
