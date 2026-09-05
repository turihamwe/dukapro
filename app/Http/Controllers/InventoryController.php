<?php

namespace App\Http\Controllers;

use App\Enums\MeasurementUnit;
use App\Helpers\AuditLogger;
use App\Models\Brand;
use App\Models\Branch;
use App\Models\Business;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\SoldByUnit;
use App\Scopes\BranchScope;
use App\Services\CatalogDiscoveryService;
use App\Services\ProductBatchService;
use App\Services\ProductInventoryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InventoryController extends Controller
{
    protected ProductInventoryService $inventoryService;

    protected CatalogDiscoveryService $catalogDiscovery;

    protected ProductBatchService $batchService;

    public function __construct(
        ProductInventoryService $inventoryService,
        CatalogDiscoveryService $catalogDiscovery,
        ProductBatchService $batchService
    ) {
        $this->inventoryService = $inventoryService;
        $this->catalogDiscovery = $catalogDiscovery;
        $this->batchService = $batchService;
        $this->authorizeResource(Product::class, 'product');
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $business = $request->user()->business;
        $branches = collect();
        $branchId = null;

        $query = Product::query()
            ->catalog()
            ->with(['brand', 'variants.activeBatches', 'activeBatches', 'branch'])
            ->withCount(['variants', 'activeBatches as active_batches_count'])
            ->orderBy('name');

        if ($request->user()->isOwner()) {
            $branches = Branch::query()
                ->where('business_id', $business->id)
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->pluck('name', 'id');

            if ($request->filled('branch_id')) {
                $branchId = (int) $request->input('branch_id');

                if ($branches->has($branchId)) {
                    $query->withoutGlobalScope(BranchScope::class)
                        ->where('products.branch_id', $branchId);
                } else {
                    $branchId = null;
                }
            }
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('sku', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('measurement_unit', 'like', '%' . $search . '%')
                    ->orWhereHas('brand', fn ($brandQuery) => $brandQuery->where('name', 'like', '%' . $search . '%'))
                    ->orWhereHas('variants', function ($variantQuery) use ($search) {
                        $variantQuery->where('sku', 'like', '%' . $search . '%');
                    });
            });
        }

        $products = $query->paginate(20)->appends(array_filter([
            'search' => $search !== '' ? $search : null,
            'branch_id' => $branchId,
        ]));

        return view('inventory.index', compact('products', 'search', 'business', 'branches', 'branchId'));
    }

    public function show(Business $business, Product $product)
    {
        if ($product->parent_id !== null) {
            return redirect()->to(tenant_route('tenant.inventory.show', ['product' => $product->parent_id]))
                ->with('info', 'Viewing parent product. Batches for this variant are listed below.');
        }

        $product->load(['brand', 'variants.activeBatches', 'activeBatches', 'batches' => fn ($q) => $q->orderByDesc('received_at')]);

        $canViewCost = auth()->user()->can('view-cost-prices');

        return view('inventory.show', compact('product', 'business', 'canViewCost'));
    }

    public function storeBatch(Request $request, Business $business, Product $product)
    {
        $this->authorize('update', $product);

        $rules = [
            'quantity' => 'required|numeric|min:0.001',
            'selling_price' => 'required|numeric|min:0',
            'received_at' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'variant_id' => 'nullable|integer',
        ];

        if ($request->user()->can('view-cost-prices')) {
            $rules['cost_price'] = 'nullable|numeric|min:0';
        }

        $data = $request->validate($rules);

        if (! $request->user()->can('view-cost-prices')) {
            unset($data['cost_price']);
        }

        $target = $product;
        if ($product->isVariableParent()) {
            if (! $request->filled('variant_id')) {
                throw ValidationException::withMessages([
                    'variant_id' => 'Select a variant for this batch.',
                ]);
            }
            $target = $product->variants()->whereKey($request->input('variant_id'))->firstOrFail();
        } elseif ($product->parent_id !== null) {
            return redirect()
                ->to(tenant_route('tenant.inventory.show', ['product' => $product->parent_id]))
                ->with('info', 'Add batches from the parent product page.');
        }

        $this->batchService->addBatch($target, $data, (int) $business->id, $request->user());

        $redirectProduct = $product->parent_id ? $product->parent : $product;

        return redirect()
            ->to(tenant_route('tenant.inventory.show', ['product' => $redirectProduct]))
            ->with('success', 'New batch added successfully.');
    }

    public function create(Request $request)
    {
        return view('inventory.create', $this->formContext($request->user()->business));
    }

    public function store(Request $request)
    {
        $business = $request->user()->business;
        $businessId = (int) $business->id;
        $productType = $request->input('product_type', 'simple');

        if ($productType === 'variable') {
            return $this->storeVariableProduct($request, $business);
        }

        $rules = $this->simpleProductRules($businessId, $request);

        $data = $request->validate($rules);

        if (! $request->user()->can('view-cost-prices')) {
            unset($data['cost_price']);
        }

        $data['brand_id'] = $this->resolveBrandId($request, $businessId);
        $data['measurement_unit'] = $this->resolveMeasurementUnit($request, $businessId, $data['measurement_unit'] ?? 'piece');

        $product = $this->inventoryService->createSimple(array_merge($data, [
            'is_active' => true,
        ]), $businessId);

        AuditLogger::record('product_created', $product, null, $product->toArray());

        return $this->storeRedirect($request, 'Product added.');
    }

    public function edit(Business $business, Product $product)
    {
        if ($product->parent_id !== null) {
            return redirect()->to(tenant_route('tenant.inventory.edit', ['product' => $product->parent_id]));
        }

        $product->load(['brand', 'variants']);

        return view('inventory.edit', array_merge(
            ['product' => $product],
            $this->formContext($business)
        ));
    }

    public function update(Request $request, Business $business, Product $product)
    {
        if ($product->parent_id !== null) {
            throw ValidationException::withMessages([
                'name' => 'Edit this item from its parent product.',
            ]);
        }

        if ($request->input('product_type') === 'variable') {
            return $this->updateVariableProduct($request, $business, $product);
        }

        if ($product->isVariableParent()) {
            throw ValidationException::withMessages([
                'name' => 'This product has variants. Keep variant mode enabled or delete the product and recreate it as a simple item.',
            ]);
        }

        $rules = $this->simpleProductRules($business->id, $request, $product->id);
        $rules['is_active'] = 'nullable|boolean';

        $data = $request->validate($rules);
        $data['is_active'] = $request->boolean('is_active');
        $data['brand_id'] = $this->resolveBrandId($request, $business->id);
        $data['measurement_unit'] = $this->resolveMeasurementUnit($request, $business->id, $data['measurement_unit'] ?? 'piece');

        if (! $request->user()->can('view-cost-prices')) {
            unset($data['cost_price']);
        }

        $this->inventoryService->updateSimple($product, $data);

        return redirect()->to(tenant_route('tenant.inventory.index'))->with('success', 'Product updated.');
    }

    public function destroy(Business $business, Product $product)
    {
        $old = $product->toArray();

        if ($product->isVariableParent()) {
            $product->variants()->delete();
        }

        $product->delete();

        AuditLogger::record('product_deleted', $product, $old, null);

        return redirect()->to(tenant_route('tenant.inventory.index'))->with('success', 'Product removed.');
    }

    protected function storeVariableProduct(Request $request, Business $business): \Illuminate\Http\RedirectResponse
    {
        $businessId = (int) $business->id;

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'brand_id' => 'nullable|exists:brands,id',
            'new_brand_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'measurement_unit' => 'required|string|max:50',
            'critical_threshold' => 'nullable|integer|min:0',
            'variants' => 'required|array|min:1',
            'variants.*.attribute_values' => 'required|array|min:1',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.stock_quantity' => 'required|numeric|min:0',
            'variants.*.sku' => 'nullable|string|max:100',
            'variants.*.cost_price' => 'nullable|numeric|min:0',
        ]);

        $data['brand_id'] = $this->resolveBrandId($request, $businessId);
        $data['measurement_unit'] = $this->resolveMeasurementUnit($request, $businessId, $data['measurement_unit'] ?? 'piece');

        if ($request->user()->can('view-cost-prices')) {
            foreach ($data['variants'] as $index => $variant) {
                if (! isset($variant['cost_price'])) {
                    $data['variants'][$index]['cost_price'] = null;
                }
            }
        } else {
            foreach ($data['variants'] as $index => $variant) {
                unset($data['variants'][$index]['cost_price']);
            }
        }

        $this->validateVariantSkus($data['variants'], $businessId);

        $parent = $this->inventoryService->createWithVariants([
            'name' => $data['name'],
            'brand_id' => $data['brand_id'],
            'description' => $data['description'] ?? null,
            'measurement_unit' => $data['measurement_unit'],
            'critical_threshold' => $data['critical_threshold'] ?? 5,
        ], $data['variants'], $businessId);

        return $this->storeRedirect($request, 'Product with ' . $parent->variants->count() . ' variants added.');
    }

    protected function updateVariableProduct(Request $request, Business $business, Product $product): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'brand_id' => 'nullable|exists:brands,id',
            'new_brand_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'measurement_unit' => 'required|string|max:50',
            'critical_threshold' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'variants' => 'required|array|min:1',
            'variants.*.id' => 'nullable|integer',
            'variants.*.attribute_values' => 'required|array|min:1',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.stock_quantity' => 'required|numeric|min:0',
            'variants.*.sku' => 'nullable|string|max:100',
            'variants.*.cost_price' => 'nullable|numeric|min:0',
        ]);

        $data['brand_id'] = $this->resolveBrandId($request, $business->id);
        $data['measurement_unit'] = $this->resolveMeasurementUnit($request, $business->id, $data['measurement_unit'] ?? 'piece');
        $data['is_active'] = $request->boolean('is_active');

        if (! $request->user()->can('view-cost-prices')) {
            foreach ($data['variants'] as $index => $variant) {
                unset($data['variants'][$index]['cost_price']);
            }
        }

        $this->validateVariantSkus($data['variants'], $business->id, $product->variants->pluck('id')->all());

        $this->inventoryService->updateVariableParent($product, [
            'name' => $data['name'],
            'brand_id' => $data['brand_id'],
            'description' => $data['description'] ?? null,
            'measurement_unit' => $data['measurement_unit'],
            'critical_threshold' => $data['critical_threshold'] ?? 5,
            'is_active' => $data['is_active'],
        ], $data['variants']);

        return redirect()->to(tenant_route('tenant.inventory.index'))->with('success', 'Product variants updated.');
    }

    protected function simpleProductRules(int $businessId, Request $request, ?int $ignoreProductId = null): array
    {
        $skuRule = Rule::unique('products', 'sku')->where(fn ($query) => $query->where('business_id', $businessId));
        if ($ignoreProductId) {
            $skuRule = $skuRule->ignore($ignoreProductId);
        }

        $rules = [
            'name' => 'required|string|max:255',
            'brand_id' => 'nullable|exists:brands,id',
            'new_brand_name' => 'nullable|string|max:255',
            'sku' => ['nullable', 'string', 'max:100', $skuRule],
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'measurement_unit' => 'required|string|max:50',
            'stock_quantity' => 'required|numeric|min:0',
            'critical_threshold' => 'nullable|integer|min:0',
        ];

        if ($request->user()->can('view-cost-prices')) {
            $rules['cost_price'] = 'nullable|numeric|min:0';
        }

        return $rules;
    }

    protected function resolveBrandId(Request $request, int $businessId): ?int
    {
        if ($request->filled('brand_id')) {
            $brand = Brand::where('business_id', $businessId)->whereKey($request->input('brand_id'))->first();
            if ($brand) {
                return $brand->id;
            }
        }

        $newBrandName = trim((string) $request->input('new_brand_name', ''));
        if ($newBrandName === '') {
            return null;
        }

        $brand = Brand::firstOrCreate(
            [
                'business_id' => $businessId,
                'name' => $newBrandName,
            ],
            [
                'slug' => Brand::uniqueSlug($businessId, $newBrandName),
                'is_active' => true,
            ]
        );

        return $brand->id;
    }

    protected function validateVariantSkus(array $variants, int $businessId, array $ignoreIds = []): void
    {
        $skus = [];
        foreach ($variants as $index => $variant) {
            $sku = trim((string) ($variant['sku'] ?? ''));
            if ($sku === '') {
                continue;
            }

            if (in_array($sku, $skus, true)) {
                throw ValidationException::withMessages([
                    "variants.$index.sku" => 'Duplicate SKU in variant list.',
                ]);
            }
            $skus[] = $sku;

            $variantId = isset($variant['id']) ? (int) $variant['id'] : null;

            $exists = Product::query()
                ->where('business_id', $businessId)
                ->where('sku', $sku)
                ->when($variantId, fn ($q) => $q->where('id', '!=', $variantId))
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    "variants.$index.sku" => 'SKU already used by another product.',
                ]);
            }
        }
    }

    public function catalog(Request $request)
    {
        $this->authorize('viewAny', Product::class);

        $businessId = (int) $request->user()->business_id;

        return response()->json([
            'brands' => Brand::query()
                ->where('business_id', $businessId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'suggested_brands' => $this->catalogDiscovery
                ->suggestedBrands($request->user()->business)
                ->map(fn ($row) => ['name' => $row->name, 'business_count' => (int) $row->business_count])
                ->values(),
            'sold_by_units' => SoldByUnit::query()
                ->where('business_id', $businessId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['slug', 'name']),
            'suggested_sold_by_units' => $this->catalogDiscovery
                ->suggestedSoldByUnits($request->user()->business)
                ->map(fn ($row) => ['slug' => $row->slug, 'name' => $row->name, 'business_count' => (int) $row->business_count])
                ->values(),
            'default_units' => MeasurementUnit::all(),
            'attributes' => ProductAttribute::query()
                ->where('business_id', $businessId)
                ->with(['values:id,product_attribute_id,value,sort_order'])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn (ProductAttribute $attribute) => [
                    'id' => $attribute->id,
                    'name' => $attribute->name,
                    'values' => $attribute->values->map(fn ($value) => [
                        'id' => $value->id,
                        'value' => $value->value,
                    ])->values(),
                ]),
        ]);
    }

    protected function formContext(Business $business): array
    {
        $attributes = ProductAttribute::query()
            ->with('values')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return [
            'business' => $business,
            'brands' => Brand::query()->where('is_active', true)->orderBy('name')->get(),
            'suggestedBrands' => $this->catalogDiscovery->suggestedBrands($business),
            'soldByUnits' => SoldByUnit::query()->where('is_active', true)->orderBy('name')->get(),
            'suggestedSoldByUnits' => $this->catalogDiscovery->suggestedSoldByUnits($business),
            'defaultUnits' => MeasurementUnit::all(),
            'attributes' => $attributes,
            'canViewCost' => auth()->user()->can('view-cost-prices'),
            'quickBrandUrl' => tenant_route('tenant.brands.quick-store'),
            'quickUnitUrl' => tenant_route('tenant.inventory.units.quick-store'),
            'quickAttributeUrl' => tenant_route('tenant.inventory.attributes.quick-store'),
            'quickValueUrl' => tenant_route('tenant.inventory.attributes.quick-value'),
            'catalogUrl' => tenant_route('tenant.inventory.catalog'),
            'businessTypeLabel' => $business->business_type
                ? \App\Enums\BusinessType::label($business->business_type)
                : null,
        ];
    }

    protected function resolveMeasurementUnit(Request $request, int $businessId, string $unit): string
    {
        $unit = trim($unit);

        if ($unit === '') {
            $unit = MeasurementUnit::PIECE;
        }

        if (in_array($unit, MeasurementUnit::all(), true)) {
            return $unit;
        }

        $newUnitName = trim((string) $request->input('new_sold_by_name', ''));

        if ($newUnitName !== '') {
            $record = SoldByUnit::firstOrCreate(
                [
                    'business_id' => $businessId,
                    'name' => $newUnitName,
                ],
                [
                    'slug' => SoldByUnit::uniqueSlug($businessId, $newUnitName),
                    'is_active' => true,
                ]
            );

            return $record->slug;
        }

        $existing = SoldByUnit::query()
            ->where('business_id', $businessId)
            ->where(function ($q) use ($unit) {
                $q->where('slug', $unit)->orWhere('name', $unit);
            })
            ->first();

        if ($existing) {
            return $existing->slug;
        }

        $record = SoldByUnit::create([
            'business_id' => $businessId,
            'name' => ucfirst(str_replace(['-', '_'], ' ', $unit)),
            'slug' => SoldByUnit::uniqueSlug($businessId, $unit),
            'is_active' => true,
        ]);

        return $record->slug;
    }

    protected function storeRedirect(Request $request, string $message): \Illuminate\Http\RedirectResponse
    {
        if ($request->boolean('add_another')) {
            return redirect()
                ->to(tenant_route('tenant.inventory.create'))
                ->with('success', $message . ' Add the next item below.');
        }

        return redirect()->to(tenant_route('tenant.inventory.index'))->with('success', $message);
    }
}
