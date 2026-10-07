@php
    $isEdit = isset($product);
    $isVariable = $isEdit && $product->isVariableParent();
    $selectedBrandId = old('brand_id', $product->brand_id ?? '');
    $catalogVariantsEnabled = $catalogVariantsEnabled ?? auth()->user()->can('use-catalog-variants');
    $showVariantFields = $catalogVariantsEnabled || ($isEdit && $isVariable);
    $productType = old('product_type', $isVariable ? 'variable' : 'simple');
    $allowedCatalogTypes = $catalogItemTypes ?? [\App\Enums\CatalogItemType::PHYSICAL];
    $selectedCatalogType = old(
        'catalog_item_type',
        isset($product) ? $product->catalogItemType() : ($defaultCatalogItemType ?? \App\Enums\CatalogItemType::PHYSICAL)
    );
    if (! in_array($selectedCatalogType, $allowedCatalogTypes, true)) {
        $selectedCatalogType = $allowedCatalogTypes[0] ?? \App\Enums\CatalogItemType::PHYSICAL;
    }
    $isServiceItem = $selectedCatalogType === \App\Enums\CatalogItemType::SERVICE;
    $catalogTypeLabels = $catalogItemTypeLabels ?? \App\Enums\CatalogItemType::labels();
    $rentalUnits = $rentalRateUnits ?? \App\Enums\CatalogItemType::rentalRateUnits();
    $variantsEnabled = $showVariantFields && $productType === 'variable';
    $canViewCost = $canViewCost ?? auth()->user()->can('view-cost-prices');

    $existingVariants = collect();
    if ($isEdit && $isVariable) {
        $existingVariants = $product->variants->map(function ($variant) {
            return [
                'id' => $variant->id,
                'attribute_values' => $variant->attribute_values ?? [],
                'sku' => $variant->sku,
                'price' => $variant->price,
                'cost_price' => $variant->cost_price,
                'stock_quantity' => $variant->stock_quantity,
            ];
        })->values();
    }

    $formConfig = [
        'existingVariants' => $existingVariants,
        'lockExistingVariants' => $isEdit && $isVariable,
        'canDeleteVariantLines' => $isEdit && $isVariable && auth()->user()->can('delete-inventory'),
        'canViewCost' => $canViewCost,
        'allowedCatalogTypes' => $allowedCatalogTypes,
        'initialCatalogType' => $selectedCatalogType,
        'showVariantFields' => $showVariantFields,
        'quickBrandUrl' => $quickBrandUrl ?? tenant_route('tenant.brands.quick-store'),
        'quickUnitUrl' => $quickUnitUrl ?? tenant_route('tenant.inventory.units.quick-store'),
        'quickAttributeUrl' => $quickAttributeUrl ?? tenant_route('tenant.inventory.attributes.quick-store'),
        'quickValueUrl' => $quickValueUrl ?? tenant_route('tenant.inventory.attributes.quick-value'),
        'catalogUrl' => $catalogUrl ?? tenant_route('tenant.inventory.catalog'),
        'attributesManageUrl' => tenant_route('tenant.inventory.attributes.index'),
    ];
@endphp

<div class="space-y-5"
     x-data="productCatalogForm(@js([
         'initialType' => $selectedCatalogType,
         'types' => $allowedCatalogTypes,
         'canViewCost' => $canViewCost,
         'showVariantFields' => $showVariantFields,
     ]))">
    @if(!empty($requireBranch) && $branches->isNotEmpty())
        <div>
            <label for="branch_id" class="mb-1 block text-sm font-medium text-gray-700">Branch</label>
            <select name="branch_id" id="branch_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value="">Select branch for this product…</option>
                @foreach($branches as $branchId => $branchName)
                    <option value="{{ $branchId }}" @selected(old('branch_id', $product->branch_id ?? null) == $branchId)>{{ $branchName }}</option>
                @endforeach
            </select>
            @error('branch_id')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    @endif

    <input type="hidden" name="product_type" id="product_type_input" value="{{ $variantsEnabled ? 'variable' : 'simple' }}">

    {{-- Brand --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <label class="mb-2 block text-sm font-medium text-gray-700" for="brand_id_select">Brand</label>
        <div class="flex flex-col gap-3 sm:flex-row">
            <select name="brand_id" id="brand_id_select"
                    class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:max-w-xs">
                <option value="">No brand</option>
                @foreach($brands ?? [] as $brand)
                    <option value="{{ $brand->id }}" @selected((string) $selectedBrandId === (string) $brand->id)>{{ $brand->name }}</option>
                @endforeach
                @if(! empty($suggestedBrands) && $suggestedBrands->isNotEmpty())
                    @foreach($suggestedBrands as $suggestedBrand)
                        <option value="" data-suggested-brand="{{ $suggestedBrand->name }}">★ {{ $suggestedBrand->name }} (popular)</option>
                    @endforeach
                @endif
            </select>
            <div class="flex min-w-0 flex-1 gap-2">
                <input type="text" id="new_brand_name" placeholder="Type a new brand and press Enter"
                       class="block min-w-0 flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <button type="button" id="add_brand_btn"
                        class="shrink-0 rounded-lg bg-gray-900 px-3 py-2 text-xs font-semibold text-white hover:bg-gray-800 disabled:opacity-50">Add</button>
            </div>
        </div>
        <p id="brand_message" class="mt-2 hidden text-xs text-emerald-600"></p>
        <p id="brand_error" class="mt-2 hidden text-xs text-red-600"></p>
    </div>

    <x-input type="text" name="name" label="Product name" value="{{ old('name', $product->name ?? '') }}" required autofocus
             placeholder="e.g. Classic T-Shirt or Guinness beer 500ml" />

    @if(count($allowedCatalogTypes) > 1)
        <div id="catalog-item-kind" class="rounded-xl border border-gray-200 bg-white p-4">
            <label for="catalog_item_type" class="mb-1.5 block text-sm font-medium text-gray-700">Item type <span class="text-red-500">*</span></label>
            <select name="catalog_item_type" id="catalog_item_type" x-model="itemType" @change="syncCatalogFields()"
                    class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('catalog_item_type') border-red-300 @enderror">
                @foreach($allowedCatalogTypes as $typeKey)
                    <option value="{{ $typeKey }}">{{ $catalogTypeLabels[$typeKey] ?? $typeKey }}</option>
                @endforeach
            </select>
            @error('catalog_item_type')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @else
                <p class="mt-1 text-xs text-gray-500">Fields below adjust automatically — services skip stock; rentals add hire rates.</p>
            @enderror
        </div>
    @else
        <input type="hidden" name="catalog_item_type" value="{{ $allowedCatalogTypes[0] ?? \App\Enums\CatalogItemType::PHYSICAL }}">
    @endif

    @if(! $isEdit)
        <div id="field-sku-barcode" x-show="showsSku()" x-cloak>
            <label class="mb-1.5 block text-sm font-medium text-gray-700" for="product_sku">SKU / barcode <span class="font-normal text-gray-400">(optional)</span></label>
            <input type="text" name="sku" id="product_sku" value="{{ old('sku') }}" maxlength="100"
                   placeholder="Leave blank to auto-generate (e.g. ABC-001)"
                   class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <p class="mt-1 text-xs text-gray-500">If empty, the system assigns a SKU using your business prefix.</p>
            @error('sku')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    @elseif($product->sku ?? null)
        <div x-show="showsSku()" x-cloak>
            <label class="mb-1 block text-sm font-medium text-gray-700">SKU / barcode</label>
            <p class="text-sm text-gray-900">{{ $product->sku }}</p>
        </div>
    @endif

    {{-- Simple product fields --}}
    <div id="simple-product-fields" class="space-y-5 {{ $variantsEnabled ? 'hidden' : '' }}">
        <div class="grid gap-5 sm:grid-cols-2" x-show="showsPricing()" x-cloak>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700" for="simple_price">
                    <span x-text="priceLabel()">Selling price</span> <span class="text-red-500">*</span>
                </label>
                <input type="number" step="0.01" min="0" name="price" id="simple_price" value="{{ old('price', $product->price ?? '') }}"
                       class="simple-field block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            @if($canViewCost)
                <div x-show="showsCost()" x-cloak>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700" for="simple_cost_price">Buying / cost price</label>
                    <input type="number" step="0.01" min="0" name="cost_price" id="simple_cost_price" value="{{ old('cost_price', $product->cost_price ?? '') }}"
                           class="simple-field block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            @endif
        </div>
        <div id="field-rental-pricing" class="grid gap-5 sm:grid-cols-2 rounded-xl border border-violet-200 bg-violet-50/60 p-4" x-show="isRentable()" x-cloak>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-800" for="rental_rate">Rental rate (UGX) <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" min="0" name="rental_rate" id="rental_rate"
                       value="{{ old('rental_rate', $product->rental_rate ?? '') }}"
                       class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500">
                @error('rental_rate')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-800" for="rental_rate_unit">Rate period <span class="text-red-500">*</span></label>
                <select name="rental_rate_unit" id="rental_rate_unit"
                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500">
                    <option value="">Select…</option>
                    @foreach($rentalUnits as $unitKey => $unitLabel)
                        <option value="{{ $unitKey }}" @selected(old('rental_rate_unit', $product->rental_rate_unit ?? '') === $unitKey)>{{ $unitLabel }}</option>
                    @endforeach
                </select>
                @error('rental_rate_unit')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <p class="sm:col-span-2 text-xs text-violet-900/80">Selling price above can be used as a default POS charge; rental rate documents hire pricing per period.</p>
        </div>
        <div id="simple-stock-wrap" x-show="showsStock()" x-cloak>
            @if($isEdit)
                <label class="mb-1.5 block text-sm font-medium text-gray-700" for="simple_stock">Stock on hand</label>
                <input type="number" step="0.001" min="0" name="stock_quantity" id="simple_stock" value="{{ old('stock_quantity', $product->stock_quantity ?? 0) }}"
                       class="simple-field block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @else
                <label class="mb-1.5 block text-sm font-medium text-gray-700" for="simple_stock">Opening stock <span class="font-normal text-gray-400">(optional)</span></label>
                <input type="number" step="0.001" min="0" name="stock_quantity" id="simple_stock" value="{{ old('stock_quantity', 0) }}"
                       class="simple-field block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <p class="mt-1 text-xs text-gray-500">Leave at 0 and use <strong>Top-up Stock</strong> later to restock existing products.</p>
            @endif
        </div>
        <p id="service-stock-hint" class="text-xs text-indigo-700" x-show="isService()" x-cloak>Stock is not tracked for services.</p>
    </div>

    @efrisPlatform
    <div class="rounded-xl border border-gray-200 bg-white p-4" x-show="!isService()" x-cloak>
        <label for="efris_item_code" class="mb-1 block text-xs font-medium text-gray-700">URA / EFRIS item code <span class="font-normal text-gray-400">(optional)</span></label>
        <input type="text" name="efris_item_code" id="efris_item_code" maxlength="100" value="{{ old('efris_item_code', $product->efris_item_code ?? '') }}"
               placeholder="Registered commodity code for fiscal receipts"
               class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @error('efris_item_code')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>
    @endefrisPlatform

    {{-- Variant toggle --}}
    @if($showVariantFields)
    <div class="flex items-center gap-3" x-show="showsVariants()" x-cloak>
        <label class="relative inline-flex shrink-0 cursor-pointer items-center">
            <input type="checkbox" id="enable_variants_toggle" class="peer sr-only" @checked($variantsEnabled)>
            <span class="block h-6 w-11 rounded-full bg-gray-300 transition-colors peer-checked:bg-indigo-600 peer-focus:ring-2 peer-focus:ring-indigo-500 peer-focus:ring-offset-2"></span>
            <span class="pointer-events-none absolute left-0.5 top-0.5 block h-5 w-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></span>
        </label>
        <div class="min-w-0">
            <p class="text-sm font-medium text-gray-900">Enable variants</p>
            <p class="text-xs text-gray-500">Size or color combinations with separate prices and stock</p>
        </div>
    </div>
    @endif

    @php
        $selectedUnit = old('measurement_unit', $product->measurement_unit ?? 'piece');
    @endphp
    <div class="rounded-xl border border-gray-200 bg-white p-4" x-show="showsUnits()" x-cloak>
        <label class="mb-2 block text-sm font-medium text-gray-700" for="measurement_unit_select">Sold by</label>
        @if(! empty($businessTypeLabel))
            <p class="mb-2 text-xs text-gray-500">Suggestions for {{ $businessTypeLabel }} businesses may appear below.</p>
        @endif
        <div class="flex flex-col gap-3 sm:flex-row">
            <select name="measurement_unit" id="measurement_unit_select" required
                    class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:max-w-xs">
                <optgroup label="Standard units">
                    @foreach($defaultUnits ?? \App\Enums\MeasurementUnit::all() as $unit)
                        <option value="{{ $unit }}" @selected($selectedUnit === $unit)>{{ ucfirst($unit) }}</option>
                    @endforeach
                </optgroup>
                @if(! empty($soldByUnits) && $soldByUnits->isNotEmpty())
                    <optgroup label="Your custom units">
                        @foreach($soldByUnits as $unit)
                            <option value="{{ $unit->slug }}" @selected($selectedUnit === $unit->slug)>{{ $unit->name }}</option>
                        @endforeach
                    </optgroup>
                @endif
                @if(! empty($suggestedSoldByUnits) && $suggestedSoldByUnits->isNotEmpty())
                    <optgroup label="Popular in your industry">
                        @foreach($suggestedSoldByUnits as $unit)
                            <option value="{{ $unit->slug }}" @selected($selectedUnit === $unit->slug)>★ {{ $unit->name }}</option>
                        @endforeach
                    </optgroup>
                @endif
            </select>
            <div class="flex min-w-0 flex-1 gap-2">
                <input type="text" id="new_sold_by_name" name="new_sold_by_name" placeholder="Add unit e.g. crate, bottle, packet"
                       class="block min-w-0 flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <button type="button" id="add_unit_btn"
                        class="shrink-0 rounded-lg bg-gray-900 px-3 py-2 text-xs font-semibold text-white hover:bg-gray-800 disabled:opacity-50">Add</button>
            </div>
        </div>
        <p id="unit_message" class="mt-2 hidden text-xs text-emerald-600"></p>
        <p id="unit_error" class="mt-2 hidden text-xs text-red-600"></p>
        <p class="mt-2 text-xs text-gray-500">The unit above is the <strong>base unit</strong> — stock is tracked in this unit (e.g. meters, kg, bottles).</p>
    </div>

    @php
        $secondaryUnits = old('secondary_units');
        if ($secondaryUnits === null && $isEdit && ! $isVariable) {
            $secondaryUnits = ($product->units ?? collect())
                ->where('is_base_unit', false)
                ->sortBy('sort_order')
                ->map(fn ($u) => [
                    'unit_name' => $u->unit_name,
                    'conversion_factor' => $u->conversion_factor,
                    'price' => $u->price,
                ])
                ->values()
                ->all();
        }
        $secondaryUnits = is_array($secondaryUnits) ? $secondaryUnits : [];
    @endphp
    <div id="secondary-units-section" class="rounded-xl border border-gray-200 bg-white p-4 {{ $variantsEnabled ? 'hidden' : '' }}" x-show="showsPackaging()" x-cloak>
        <div class="mb-3">
            <h3 class="text-sm font-semibold text-gray-900">Packaging units <span class="font-normal text-gray-400">(optional)</span></h3>
            <p class="mt-1 text-xs text-gray-500">Add larger units sold at the POS (e.g. 1 roll = 50 meters). Stock is always deducted in the base unit.</p>
        </div>
        <div id="secondary-units-rows" class="space-y-3">
            @forelse($secondaryUnits as $idx => $row)
                <div class="secondary-unit-row grid gap-3 sm:grid-cols-12 sm:items-end">
                    <div class="sm:col-span-4">
                        <label class="mb-1 block text-xs font-medium text-gray-600">Unit name</label>
                        <input type="text" name="secondary_units[{{ $idx }}][unit_name]" value="{{ $row['unit_name'] ?? '' }}" placeholder="e.g. roll, crate"
                               class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="mb-1 block text-xs font-medium text-gray-600">= base units</label>
                        <input type="number" step="0.001" min="0.001" name="secondary_units[{{ $idx }}][conversion_factor]" value="{{ $row['conversion_factor'] ?? '' }}" placeholder="50"
                               class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="mb-1 block text-xs font-medium text-gray-600">Price <span class="font-normal text-gray-400">(optional)</span></label>
                        <input type="number" step="0.01" min="0" name="secondary_units[{{ $idx }}][price]" value="{{ $row['price'] ?? '' }}" placeholder="Auto from base price"
                               class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="sm:col-span-2">
                        <button type="button" class="remove-secondary-unit w-full rounded-lg border border-red-200 px-3 py-2 text-xs font-medium text-red-600 hover:bg-red-50">Remove</button>
                    </div>
                </div>
            @empty
            @endforelse
        </div>
        <button type="button" id="add-secondary-unit-btn" class="mt-3 rounded-lg border border-dashed border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:border-indigo-300 hover:text-indigo-700">+ Add packaging unit</button>
        @error('product_units')
            <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div x-show="showsStockAlerts()" x-cloak>
        <x-input type="number" step="1" name="critical_threshold" label="Low-stock alert threshold" value="{{ old('critical_threshold', $product->critical_threshold ?? 5) }}" />
    </div>

    @if($showVariantFields)
    {{-- Variant builder --}}
    <div id="variant-product-fields" class="{{ $variantsEnabled ? '' : 'hidden' }}" x-show="showsVariants()" x-cloak>
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-5 space-y-5">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">Attributes &amp; variants</h3>
                    <p class="mt-1 text-xs text-gray-500">
                        @if($isEdit && $isVariable)
                            Options already used by a variant are <strong>locked</strong> so you cannot remove a line by accident. Add new sizes/colors by ticking new options or <strong>+ Add variant</strong>. To drop a sellable line, use <strong>Remove</strong> on that row.
                        @else
                            Each sellable SKU is one row in the table below. Tick multiple options (e.g. S, M, and L) or click <strong>+ Add variant</strong> repeatedly to add one row at a time. Use <strong>+ Add attribute</strong> only for new types (Size vs Color).
                        @endif
                    </p>
                </div>
                <a href="{{ tenant_route('tenant.inventory.attributes.index') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Manage attributes →</a>
            </div>

            <div class="rounded-lg border border-dashed border-gray-300 bg-white p-4">
                <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500">Quick-add attribute</p>
                <div class="grid gap-3 sm:grid-cols-3">
                    <input type="text" id="new_attribute_name" placeholder="e.g. Size" class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <input type="text" id="new_attribute_values" placeholder="S, M, L, XL" class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:col-span-2">
                </div>
                <button type="button" id="add_attribute_btn" class="mt-3 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">+ Add attribute</button>
                <p id="attribute_error" class="mt-2 hidden text-xs text-red-600"></p>
            </div>

            <div id="attribute-picker-list" class="space-y-4">
                @forelse($attributes ?? [] as $attribute)
                    <div class="attribute-picker rounded-lg border border-gray-200 bg-white p-4" data-attribute-id="{{ $attribute->id }}" data-attribute-name="{{ $attribute->name }}">
                        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-medium text-gray-800">{{ $attribute->name }}</p>
                            @if($attribute->values->isNotEmpty())
                                <div class="flex items-center gap-2">
                                    <button type="button" class="select-all-attribute-btn text-xs font-medium text-indigo-600 hover:text-indigo-800" data-attribute-id="{{ $attribute->id }}">Select all</button>
                                    <button type="button" class="unselect-all-attribute-btn text-xs font-medium text-indigo-600 hover:text-indigo-800" data-attribute-id="{{ $attribute->id }}">Unselect all</button>
                                </div>
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @forelse($attribute->values as $value)
                                <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm text-gray-700 hover:border-gray-300">
                                    <input type="checkbox" class="variant-value-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                           data-attribute-id="{{ $attribute->id }}" value="{{ $value->value }}">
                                    <span>{{ $value->value }}</span>
                                </label>
                            @empty
                                <span class="text-xs text-gray-400">No values yet — add one below.</span>
                            @endforelse
                        </div>
                        <div class="mt-3 flex gap-2">
                            <input type="text" class="new-attribute-value-input min-w-0 flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="Add {{ $attribute->name }} option…" data-attribute-id="{{ $attribute->id }}">
                            <button type="button" class="add-attribute-value-btn shrink-0 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                    data-attribute-id="{{ $attribute->id }}">Add option</button>
                        </div>
                    </div>
                @empty
                    <p id="no-attributes-msg" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">No attributes yet. Add Size or Color above, or use Manage attributes.</p>
                @endforelse
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button type="button" id="add_variant_row_btn"
                        class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50">
                    + Add variant
                </button>
                <p id="variant-row-hint" class="text-xs text-gray-500">Adds the next size/color row, or tick several options above for many rows at once. Unfilled rows and rows with only default 0 stock are skipped on save.</p>
            </div>
            @php
                $variantErrorMessages = [];
                foreach ($errors->keys() as $errorKey) {
                    if ($errorKey === 'variants' || strpos($errorKey, 'variants.') === 0) {
                        foreach ($errors->get($errorKey) as $message) {
                            $variantErrorMessages[] = $message;
                        }
                    }
                }
            @endphp
            @if(count($variantErrorMessages))
                <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800" role="alert">
                    @foreach($variantErrorMessages as $message)
                        <p>{{ $message }}</p>
                    @endforeach
                </div>
            @endif
            <p id="variant_notice" class="hidden rounded-lg border px-3 py-2 text-sm border-amber-200 bg-amber-50 text-amber-900" role="status"></p>

            <div id="variant-table-empty" class="rounded-lg border border-dashed border-gray-300 bg-white px-4 py-6 text-center text-sm text-gray-500">
                No variant rows yet. Tick options above or click <strong>+ Add variant</strong>.
            </div>

            @if($isEdit && $isVariable)
                <div id="deleted-variant-ids-container" aria-hidden="true"></div>
            @endif

            <div id="variant-table-wrap" class="hidden overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-white">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium text-gray-500">Variant</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-500">Stock</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-500">Price</th>
                            @if($canViewCost)
                                <th class="px-3 py-2 text-left font-medium text-gray-500">Cost</th>
                            @endif
                            @if($isEdit && $isVariable && auth()->user()->can('delete-inventory'))
                                <th class="px-3 py-2 text-left font-medium text-gray-500 w-24"></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody id="variant-rows-tbody" class="divide-y divide-gray-100 bg-white"></tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <x-textarea name="description" label="Notes (optional)" rows="2">{{ old('description', $product->description ?? '') }}</x-textarea>

    @if($isEdit)
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
            Active (visible in POS when in stock)
        </label>
    @endif
</div>

<script type="application/json" id="product-form-config">@json($formConfig)</script>

@push('styles')
<style>
[x-cloak]{display:none!important}
.variant-value-checkbox:disabled{opacity:.65;cursor:not-allowed}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('alpine:init', function () {
    Alpine.data('productCatalogForm', function (config) {
        config = config || {};
        return {
            itemType: config.initialType || 'physical',
            canViewCost: !!config.canViewCost,
            showVariantFields: !!config.showVariantFields,
            isPhysical: function () { return this.itemType === 'physical'; },
            isService: function () { return this.itemType === 'service'; },
            isRentable: function () { return this.itemType === 'rentable'; },
            showsSku: function () { return ! this.isService(); },
            showsPricing: function () { return true; },
            showsCost: function () { return this.canViewCost && (this.isPhysical() || this.isRentable()); },
            showsStock: function () { return this.isPhysical() || this.isRentable(); },
            showsStockAlerts: function () { return this.showsStock() && ! this.isRentable(); },
            showsUnits: function () { return ! this.isService(); },
            showsPackaging: function () { return this.isPhysical(); },
            showsVariants: function () { return this.showVariantFields && this.isPhysical(); },
            priceLabel: function () {
                if (this.isService()) return 'Service fee';
                if (this.isRentable()) return 'Default POS price';
                return 'Selling price';
            },
            syncCatalogFields: function () {
                var stockInput = document.getElementById('simple_stock');
                var priceInput = document.getElementById('simple_price');
                var unitSelect = document.getElementById('measurement_unit_select');
                var variantToggle = document.getElementById('enable_variants_toggle');
                if (this.isService() && stockInput) stockInput.value = '0';
                if (priceInput) {
                    priceInput.setAttribute('required', 'required');
                }
                if (unitSelect) {
                    if (this.isService()) {
                        unitSelect.removeAttribute('required');
                    } else {
                        unitSelect.setAttribute('required', 'required');
                    }
                }
                if (variantToggle && ! this.isPhysical() && variantToggle.checked) {
                    variantToggle.checked = false;
                    variantToggle.dispatchEvent(new Event('change'));
                }
                if (typeof window.productFormSyncVariantMode === 'function') {
                    window.productFormSyncVariantMode();
                }
            },
            init: function () {
                this.syncCatalogFields();
            },
        };
    });
});
</script>
<script>
(function () {
    var config = {};
    try {
        config = JSON.parse(document.getElementById('product-form-config').textContent || '{}');
    } catch (e) {
        config = {};
    }

    var csrf = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrf ? csrf.content : '';
    var canViewCost = !!config.canViewCost;
    var lockExistingVariants = !!config.lockExistingVariants;
    var canDeleteVariantLines = !!config.canDeleteVariantLines;
    var savedVariants = config.existingVariants || [];
    var deletedVariantIds = {};
    var rowState = {};

    function esc(text) {
        var div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    function showMsg(el, text, isError) {
        if (!el) return;
        el.textContent = text;
        el.classList.toggle('hidden', !text);
        el.classList.toggle('text-red-600', !!isError);
        el.classList.toggle('text-emerald-600', !isError);
    }

    function showVariantNotice(text, isError) {
        showMsg(document.getElementById('variant_notice'), text, !!isError);
    }

    function clearVariantNotice() {
        showMsg(document.getElementById('variant_notice'), '', false);
    }

    function notifyIfSelectionOnlyExisting(checkbox) {
        if (!checkbox || !checkbox.checked) {
            clearVariantNotice();
            return;
        }
        var picker = checkbox.closest('.attribute-picker');
        var attrName = picker ? picker.getAttribute('data-attribute-name') : '';
        if (!attrName) return;
        var value = checkbox.value;
        var combos = cartesianCombinations(getSelectedValues());
        var related = combos.filter(function (c) {
            return String(c[attrName]) === String(value);
        });
        if (!related.length) return;
        var allExisting = related.every(function (c) {
            var saved = savedVariants.find(function (variant) {
                if (deletedVariantIds[variant.id]) return false;
                var attrs = variant.attribute_values || {};
                var keys = Object.keys(c);
                if (keys.length !== Object.keys(attrs).length) return false;
                return keys.every(function (k) { return String(attrs[k]) === String(c[k]); });
            });
            return saved && saved.id;
        });
        if (allExisting) {
            showVariantNotice('This variant already exists — update price or stock in the table below.');
        } else {
            clearVariantNotice();
        }
    }

    // --- Simple / variant mode toggle ---
    var toggle = document.getElementById('enable_variants_toggle');
    var simple = document.getElementById('simple-product-fields');
    var variant = document.getElementById('variant-product-fields');
    var secondaryUnitsSection = document.getElementById('secondary-units-section');
    var typeInput = document.getElementById('product_type_input');
    var catalogKind = document.getElementById('catalog-item-kind');

    function syncVariantMode() {
        if (!toggle || !simple || !variant || !typeInput) return;
        var on = toggle.checked;
        simple.classList.toggle('hidden', on);
        variant.classList.toggle('hidden', !on);
        if (secondaryUnitsSection) secondaryUnitsSection.classList.toggle('hidden', on);
        if (catalogKind) catalogKind.classList.toggle('hidden', on);
        typeInput.value = on ? 'variable' : 'simple';
        simple.querySelectorAll('.simple-field').forEach(function (field) {
            field.disabled = on;
            if (on) {
                field.removeAttribute('required');
            } else if (field.id === 'simple_price' || field.id === 'simple_stock') {
                field.setAttribute('required', 'required');
            }
        });
        if (on) rebuildVariantTable();
    }

    window.productFormSyncVariantMode = syncVariantMode;

    if (toggle) {
        toggle.addEventListener('change', syncVariantMode);
        syncVariantMode();
    }

    // --- Secondary packaging units ---
    var secondaryRows = document.getElementById('secondary-units-rows');
    var addSecondaryBtn = document.getElementById('add-secondary-unit-btn');
    var secondaryRowIndex = secondaryRows ? secondaryRows.querySelectorAll('.secondary-unit-row').length : 0;

    function secondaryUnitRowHtml(idx) {
        return '<div class="secondary-unit-row grid gap-3 sm:grid-cols-12 sm:items-end">' +
            '<div class="sm:col-span-4"><label class="mb-1 block text-xs font-medium text-gray-600">Unit name</label>' +
            '<input type="text" name="secondary_units[' + idx + '][unit_name]" placeholder="e.g. roll, crate" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></div>' +
            '<div class="sm:col-span-3"><label class="mb-1 block text-xs font-medium text-gray-600">= base units</label>' +
            '<input type="number" step="0.001" min="0.001" name="secondary_units[' + idx + '][conversion_factor]" placeholder="50" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></div>' +
            '<div class="sm:col-span-3"><label class="mb-1 block text-xs font-medium text-gray-600">Price <span class="font-normal text-gray-400">(optional)</span></label>' +
            '<input type="number" step="0.01" min="0" name="secondary_units[' + idx + '][price]" placeholder="Auto from base price" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></div>' +
            '<div class="sm:col-span-2"><button type="button" class="remove-secondary-unit w-full rounded-lg border border-red-200 px-3 py-2 text-xs font-medium text-red-600 hover:bg-red-50">Remove</button></div>' +
        '</div>';
    }

    if (addSecondaryBtn && secondaryRows) {
        addSecondaryBtn.addEventListener('click', function () {
            secondaryRows.insertAdjacentHTML('beforeend', secondaryUnitRowHtml(secondaryRowIndex++));
        });
        secondaryRows.addEventListener('click', function (e) {
            var btn = e.target.closest('.remove-secondary-unit');
            if (!btn) return;
            var row = btn.closest('.secondary-unit-row');
            if (row) row.remove();
        });
    }

    // --- Brand quick-add ---
    var brandSelect = document.getElementById('brand_id_select');
    var brandInput = document.getElementById('new_brand_name');
    var brandBtn = document.getElementById('add_brand_btn');
    var brandMessage = document.getElementById('brand_message');
    var brandError = document.getElementById('brand_error');

    function addBrand() {
        var name = brandInput ? brandInput.value.trim() : '';
        if (!name || !config.quickBrandUrl) return;
        brandBtn.disabled = true;
        showMsg(brandMessage, '', false);
        showMsg(brandError, '', true);
        fetch(config.quickBrandUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ name: name }),
        })
        .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
        .then(function (result) {
            if (!result.ok) {
                var err = result.data.errors ? Object.values(result.data.errors).flat()[0] : null;
                throw new Error(err || result.data.message || 'Could not add brand.');
            }
            if (!brandSelect.querySelector('option[value="' + result.data.id + '"]')) {
                var opt = document.createElement('option');
                opt.value = result.data.id;
                opt.textContent = result.data.name;
                brandSelect.appendChild(opt);
            }
            brandSelect.value = String(result.data.id);
            brandInput.value = '';
            showMsg(brandMessage, 'Brand added and selected.', false);
        })
        .catch(function (e) {
            showMsg(brandError, e.message || 'Could not add brand.', true);
        })
        .finally(function () { brandBtn.disabled = false; });
    }

    if (brandBtn) brandBtn.addEventListener('click', addBrand);
    if (brandInput) brandInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); addBrand(); }
    });
    if (brandSelect) brandSelect.addEventListener('change', function () {
        var opt = brandSelect.options[brandSelect.selectedIndex];
        var suggested = opt ? opt.getAttribute('data-suggested-brand') : null;
        if (suggested && brandInput) {
            brandInput.value = suggested;
            addBrand();
        }
    });

    // --- Sold-by unit quick add ---
    var unitSelect = document.getElementById('measurement_unit_select');
    var unitInput = document.getElementById('new_sold_by_name');
    var unitBtn = document.getElementById('add_unit_btn');
    var unitMessage = document.getElementById('unit_message');
    var unitError = document.getElementById('unit_error');

    function addUnit() {
        var name = unitInput ? unitInput.value.trim() : '';
        if (!name || !config.quickUnitUrl) return;
        unitBtn.disabled = true;
        showMsg(unitMessage, '', false);
        showMsg(unitError, '', true);
        fetch(config.quickUnitUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ name: name }),
        })
        .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
        .then(function (result) {
            if (!result.ok) {
                var err = result.data.errors ? Object.values(result.data.errors).flat()[0] : null;
                throw new Error(err || result.data.message || 'Could not add unit.');
            }
            if (!unitSelect.querySelector('option[value="' + result.data.slug + '"]')) {
                var opt = document.createElement('option');
                opt.value = result.data.slug;
                opt.textContent = result.data.name;
                unitSelect.appendChild(opt);
            }
            unitSelect.value = result.data.slug;
            unitInput.value = '';
            showMsg(unitMessage, 'Unit added and selected.', false);
        })
        .catch(function (e) {
            showMsg(unitError, e.message || 'Could not add unit.', true);
        })
        .finally(function () { unitBtn.disabled = false; });
    }

    if (unitBtn) unitBtn.addEventListener('click', addUnit);
    if (unitInput) unitInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); addUnit(); }
    });

    // --- Variant table builder ---
    function comboKey(values) {
        return Object.keys(values).sort().map(function (k) { return k + ':' + values[k]; }).join('|');
    }

    function getSelectedValues() {
        var groups = [];
        document.querySelectorAll('.attribute-picker').forEach(function (picker) {
            var attrName = picker.getAttribute('data-attribute-name');
            var values = [];
            picker.querySelectorAll('.variant-value-checkbox:checked').forEach(function (cb) {
                values.push(cb.value);
            });
            if (values.length) groups.push({ name: attrName, values: values });
        });
        return groups;
    }

    function snapshotCheckedOptions() {
        var selected = {};
        document.querySelectorAll('.attribute-picker').forEach(function (picker) {
            var attrName = picker.getAttribute('data-attribute-name');
            if (!attrName) return;
            selected[attrName] = [];
            picker.querySelectorAll('.variant-value-checkbox:checked').forEach(function (cb) {
                selected[attrName].push(cb.value);
            });
        });
        return selected;
    }

    function restoreCheckedOptions(selected) {
        if (!selected) return;
        Object.keys(selected).forEach(function (attrName) {
            (selected[attrName] || []).forEach(function (value) {
                document.querySelectorAll('.attribute-picker').forEach(function (picker) {
                    if (picker.getAttribute('data-attribute-name') !== attrName) return;
                    picker.querySelectorAll('.variant-value-checkbox').forEach(function (cb) {
                        if (cb.value === String(value)) cb.checked = true;
                    });
                });
            });
        });
    }

    function cartesianCombinations(groups) {
        if (!groups.length) return [];
        var results = [{}];
        groups.forEach(function (group) {
            var next = [];
            results.forEach(function (base) {
                group.values.forEach(function (value) {
                    var row = Object.assign({}, base);
                    row[group.name] = value;
                    next.push(row);
                });
            });
            results = next;
        });
        return results;
    }

    function findSavedMatch(values) {
        return savedVariants.find(function (variant) {
            if (deletedVariantIds[variant.id]) return false;
            var attrs = variant.attribute_values || {};
            var keys = Object.keys(values);
            if (keys.length !== Object.keys(attrs).length) return false;
            return keys.every(function (k) { return String(attrs[k]) === String(values[k]); });
        });
    }

    function attributeValueInUseByActiveVariant(attrName, value) {
        return savedVariants.some(function (variant) {
            if (deletedVariantIds[variant.id]) return false;
            var attrs = variant.attribute_values || {};
            return String(attrs[attrName]) === String(value);
        });
    }

    function updateAttributeCheckboxLocks() {
        if (!lockExistingVariants) return;
        document.querySelectorAll('.variant-value-checkbox').forEach(function (cb) {
            var picker = cb.closest('.attribute-picker');
            var attrName = picker ? picker.getAttribute('data-attribute-name') : '';
            var inUse = attrName && attributeValueInUseByActiveVariant(attrName, cb.value);
            if (inUse) {
                cb.checked = true;
                cb.disabled = true;
            } else {
                cb.disabled = false;
            }
        });
    }

    function renderDeletedVariantInputs() {
        var container = document.getElementById('deleted-variant-ids-container');
        if (!container) return;
        container.innerHTML = '';
        Object.keys(deletedVariantIds).forEach(function (id) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'deleted_variant_ids[]';
            input.value = id;
            container.appendChild(input);
        });
    }

    function removeVariantLine(variantId, label) {
        var detail = (label ? label + ' — ' : '')
            + 'It will be hidden from POS and inventory (soft delete). Past sales on receipts are kept. Save the product to apply.';
        if (typeof window.requestDeleteConfirmation === 'function') {
            window.requestDeleteConfirmation({
                message: 'Are you sure you want to delete this item?',
                detail: detail,
                onConfirm: function () {
                    deletedVariantIds[variantId] = true;
                    updateAttributeCheckboxLocks();
                    rebuildVariantTable();
                    renderDeletedVariantInputs();
                },
            });
            return;
        }
        deletedVariantIds[variantId] = true;
        updateAttributeCheckboxLocks();
        rebuildVariantTable();
        renderDeletedVariantInputs();
    }

    function syncVariantTableVisibility(comboCount) {
        var wrap = document.getElementById('variant-table-wrap');
        var empty = document.getElementById('variant-table-empty');
        if (wrap) wrap.classList.toggle('hidden', comboCount < 1);
        if (empty) empty.classList.toggle('hidden', comboCount > 0);
    }

    function checkOptionForAttribute(attrName, value) {
        document.querySelectorAll('.attribute-picker').forEach(function (picker) {
            if (picker.getAttribute('data-attribute-name') !== attrName) return;
            picker.querySelectorAll('.variant-value-checkbox').forEach(function (cb) {
                if (cb.value === String(value)) cb.checked = true;
            });
        });
    }

    function addVariantRow() {
        var pickers = document.querySelectorAll('.attribute-picker');
        if (!pickers.length) {
            alert('Create an attribute first — e.g. name “Size” and values “S, M, L” in the quick-add box above.');
            return;
        }
        for (var i = 0; i < pickers.length; i++) {
            var unchecked = pickers[i].querySelector('.variant-value-checkbox:not(:checked):not(:disabled)');
            if (unchecked) {
                unchecked.checked = true;
                rebuildVariantTable();
                notifyIfSelectionOnlyExisting(unchecked);
                var wrap = document.getElementById('variant-table-wrap');
                if (wrap) wrap.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                return;
            }
        }
        alert('Every listed option is already a variant. Type a new option (e.g. XL) under the attribute and click “Add option”, then + Add variant again.');
    }

    function rebuildVariantTable() {
        var tbody = document.getElementById('variant-rows-tbody');
        var wrap = document.getElementById('variant-table-wrap');
        if (!tbody || !wrap) return;

        var combos = cartesianCombinations(getSelectedValues()).filter(function (attributeValues) {
            var saved = findSavedMatch(attributeValues);
            return !(saved && saved.id && deletedVariantIds[saved.id]);
        });
        tbody.innerHTML = '';

        syncVariantTableVisibility(combos.length);
        if (!combos.length) {
            return;
        }

        combos.forEach(function (attributeValues, index) {
            var key = comboKey(attributeValues);
            var saved = findSavedMatch(attributeValues) || {};
            var prev = rowState[key] || {};
            var label = Object.keys(attributeValues).sort().map(function (k) {
                return k + ': ' + attributeValues[k];
            }).join(' · ');

            rowState[key] = {
                price: prev.price != null ? prev.price : (saved.price || ''),
                cost_price: prev.cost_price != null ? prev.cost_price : (saved.cost_price || ''),
                stock_quantity: prev.stock_quantity != null ? prev.stock_quantity : (saved.stock_quantity != null ? saved.stock_quantity : 0),
                id: saved.id || prev.id || null,
            };

            var tr = document.createElement('tr');
            var attrsHtml = Object.keys(attributeValues).sort().map(function (name) {
                return '<input type="hidden" name="variants[' + index + '][attribute_values][' + name + ']" value="' + esc(attributeValues[name]) + '">';
            }).join('');
            var idHtml = rowState[key].id ? '<input type="hidden" name="variants[' + index + '][id]" value="' + esc(rowState[key].id) + '">' : '';
            var removeHtml = '';
            if (lockExistingVariants && canDeleteVariantLines && rowState[key].id) {
                removeHtml = '<td class="px-3 py-2 text-right">' +
                    '<button type="button" class="remove-variant-line-btn text-xs font-medium text-red-600 hover:text-red-800" data-variant-id="' + esc(rowState[key].id) + '" data-variant-label="' + esc(label) + '">Remove</button>' +
                    '</td>';
            } else if (lockExistingVariants && canDeleteVariantLines) {
                removeHtml = '<td class="px-3 py-2"></td>';
            }

            tr.innerHTML =
                '<td class="px-3 py-2 text-gray-900">' + esc(label) + attrsHtml + idHtml + '</td>' +
                '<td class="px-3 py-2"><input type="number" step="0.001" min="0" name="variants[' + index + '][stock_quantity]" value="' + esc(rowState[key].stock_quantity) + '" class="w-full min-w-[72px] rounded-lg border-gray-300 text-sm variant-field" data-key="' + esc(key) + '" data-field="stock_quantity"></td>' +
                '<td class="px-3 py-2"><input type="number" step="0.01" min="0" name="variants[' + index + '][price]" value="' + esc(rowState[key].price) + '" class="w-full min-w-[80px] rounded-lg border-gray-300 text-sm variant-field" data-key="' + esc(key) + '" data-field="price"></td>' +
                (canViewCost ? '<td class="px-3 py-2"><input type="number" step="0.01" min="0" name="variants[' + index + '][cost_price]" value="' + esc(rowState[key].cost_price) + '" class="w-full min-w-[80px] rounded-lg border-gray-300 text-sm variant-field" data-key="' + esc(key) + '" data-field="cost_price"></td>' : '') +
                removeHtml;

            tbody.appendChild(tr);
        });

        tbody.querySelectorAll('.remove-variant-line-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                removeVariantLine(btn.getAttribute('data-variant-id'), btn.getAttribute('data-variant-label'));
            });
        });

        tbody.querySelectorAll('.variant-field').forEach(function (input) {
            input.addEventListener('input', function () {
                var k = input.getAttribute('data-key');
                var f = input.getAttribute('data-field');
                if (!rowState[k]) rowState[k] = {};
                rowState[k][f] = input.value;
            });
        });
    }

    var attributePickerList = document.getElementById('attribute-picker-list');
    if (attributePickerList) {
        attributePickerList.addEventListener('change', function (e) {
            if (e.target && e.target.classList.contains('variant-value-checkbox')) {
                if (e.target.disabled && !e.target.checked) {
                    e.target.checked = true;
                    return;
                }
                rebuildVariantTable();
                if (e.target.checked) {
                    notifyIfSelectionOnlyExisting(e.target);
                } else {
                    clearVariantNotice();
                }
            }
        });
        attributePickerList.addEventListener('click', function (e) {
            var selectAllBtn = e.target.closest('.select-all-attribute-btn');
            if (selectAllBtn) {
                var picker = selectAllBtn.closest('.attribute-picker');
                if (picker) {
                    picker.querySelectorAll('.variant-value-checkbox:not(:disabled)').forEach(function (cb) {
                        cb.checked = true;
                    });
                    rebuildVariantTable();
                }
                return;
            }
            var unselectAllBtn = e.target.closest('.unselect-all-attribute-btn');
            if (unselectAllBtn) {
                var unselectPicker = unselectAllBtn.closest('.attribute-picker');
                if (unselectPicker) {
                    unselectPicker.querySelectorAll('.variant-value-checkbox:not(:disabled)').forEach(function (cb) {
                        cb.checked = false;
                    });
                    rebuildVariantTable();
                }
                return;
            }
            var btn = e.target.closest('.add-attribute-value-btn');
            if (!btn) return;
            var attributeId = btn.getAttribute('data-attribute-id');
            var picker = btn.closest('.attribute-picker');
            var input = picker ? picker.querySelector('.new-attribute-value-input') : null;
            var value = input ? input.value.trim() : '';
            if (!value || !config.quickValueUrl) return;
            fetch(config.quickValueUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ attribute_id: attributeId, value: value }),
            })
            .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
            .then(function (result) {
                if (!result.ok) throw new Error(result.data.message || 'Could not add value.');
                if (result.data.created === false) {
                    showVariantNotice('That option already exists in your attribute list.');
                } else {
                    clearVariantNotice();
                }
                if (input) input.value = '';
                var attrName = picker ? picker.getAttribute('data-attribute-name') : null;
                return refreshCatalog().then(function () {
                    if (attrName && value) checkOptionForAttribute(attrName, value);
                });
            })
            .then(function () { rebuildVariantTable(); })
            .catch(function (e) { alert(e.message || 'Could not add value.'); });
        });
    }

    var addVariantRowBtn = document.getElementById('add_variant_row_btn');
    if (addVariantRowBtn) {
        addVariantRowBtn.addEventListener('click', addVariantRow);
    }

    // Seed checkboxes from saved variants on edit
    savedVariants.forEach(function (variant) {
        Object.entries(variant.attribute_values || {}).forEach(function (entry) {
            var attrName = entry[0];
            var value = entry[1];
            document.querySelectorAll('.attribute-picker').forEach(function (picker) {
                if (picker.getAttribute('data-attribute-name') !== attrName) return;
                picker.querySelectorAll('.variant-value-checkbox').forEach(function (cb) {
                    if (cb.value === String(value)) cb.checked = true;
                });
            });
        });
    });
    updateAttributeCheckboxLocks();
    rebuildVariantTable();

    // --- Attribute quick-add ---
    function renderAttributePickers(attributes) {
        var list = document.getElementById('attribute-picker-list');
        if (!list) return;
        if (!attributes.length) {
            list.innerHTML = '<p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">No attributes yet. Add Size or Color above, or use Manage attributes.</p>';
            return;
        }
        list.innerHTML = attributes.map(function (attribute) {
            var valuesHtml = (attribute.values || []).map(function (val) {
                return '<label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm text-gray-700 hover:border-gray-300">' +
                    '<input type="checkbox" class="variant-value-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" data-attribute-id="' + esc(attribute.id) + '" value="' + esc(val.value) + '">' +
                    '<span>' + esc(val.value) + '</span></label>';
            }).join('') || '<span class="text-xs text-gray-400">No values yet — add one below.</span>';
            var selectAllHtml = (attribute.values && attribute.values.length)
                ? '<div class="flex items-center gap-2">' +
                '<button type="button" class="select-all-attribute-btn text-xs font-medium text-indigo-600 hover:text-indigo-800" data-attribute-id="' + esc(attribute.id) + '">Select all</button>' +
                '<button type="button" class="unselect-all-attribute-btn text-xs font-medium text-indigo-600 hover:text-indigo-800" data-attribute-id="' + esc(attribute.id) + '">Unselect all</button>' +
                '</div>'
                : '';
            return '<div class="attribute-picker rounded-lg border border-gray-200 bg-white p-4" data-attribute-id="' + esc(attribute.id) + '" data-attribute-name="' + esc(attribute.name) + '">' +
                '<div class="mb-2 flex flex-wrap items-center justify-between gap-2">' +
                '<p class="text-sm font-medium text-gray-800">' + esc(attribute.name) + '</p>' + selectAllHtml + '</div>' +
                '<div class="flex flex-wrap gap-2">' + valuesHtml + '</div>' +
                '<div class="mt-3 flex gap-2">' +
                '<input type="text" class="new-attribute-value-input min-w-0 flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Add ' + esc(attribute.name) + ' option…" data-attribute-id="' + esc(attribute.id) + '">' +
                '<button type="button" class="add-attribute-value-btn shrink-0 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50" data-attribute-id="' + esc(attribute.id) + '">Add option</button>' +
                '</div></div>';
        }).join('');
    }

    function refreshCatalog() {
        if (!config.catalogUrl) return Promise.resolve();
        var checked = snapshotCheckedOptions();
        return fetch(config.catalogUrl, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
        .then(function (res) { return res.ok ? res.json() : null; })
        .then(function (data) {
            if (data && data.attributes) {
                renderAttributePickers(data.attributes);
                restoreCheckedOptions(checked);
                updateAttributeCheckboxLocks();
            }
        });
    }

    var addAttributeBtn = document.getElementById('add_attribute_btn');
    if (addAttributeBtn) {
        addAttributeBtn.addEventListener('click', function () {
            var nameInput = document.getElementById('new_attribute_name');
            var valuesInput = document.getElementById('new_attribute_values');
            var name = nameInput ? nameInput.value.trim() : '';
            var valuesText = valuesInput ? valuesInput.value.trim() : '';
            var valuesToSelect = valuesText ? valuesText.split(/\s*,\s*/).filter(Boolean) : [];
            if (!name || !config.quickAttributeUrl) return;
            addAttributeBtn.disabled = true;
            showMsg(document.getElementById('attribute_error'), '', true);
            fetch(config.quickAttributeUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ name: name, values_text: valuesText }),
            })
            .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
            .then(function (result) {
                if (!result.ok) {
                    var err = result.data.errors ? Object.values(result.data.errors).flat()[0] : null;
                    throw new Error(err || result.data.message || 'Could not add attribute.');
                }
                var attrName = (result.data.attribute && result.data.attribute.name) ? result.data.attribute.name : name;
                if (nameInput) nameInput.value = '';
                if (valuesInput) valuesInput.value = '';
                showMsg(document.getElementById('attribute_error'), valuesToSelect.length
                    ? 'Attribute added — variant rows created for each value.'
                    : 'Attribute added. Add options or click + Add variant.', false);
                return refreshCatalog().then(function () {
                    valuesToSelect.forEach(function (v) { checkOptionForAttribute(attrName, v); });
                });
            })
            .then(function () { rebuildVariantTable(); })
            .catch(function (e) {
                showMsg(document.getElementById('attribute_error'), e.message || 'Could not add attribute.', true);
            })
            .finally(function () { addAttributeBtn.disabled = false; });
        });
    }

    function variantRowLabelFromTr(tr) {
        var cell = tr.querySelector('td:first-child');
        return cell ? cell.textContent.trim() : 'This variant';
    }

    function setVariantRowSubmitExcluded(tr, exclude) {
        tr.querySelectorAll('input, select, textarea').forEach(function (input) {
            if (exclude) {
                input.disabled = true;
                input.setAttribute('data-variant-submit-excluded', '1');
            } else if (input.getAttribute('data-variant-submit-excluded') === '1') {
                input.disabled = false;
                input.removeAttribute('data-variant-submit-excluded');
            }
        });
    }

    function prepareVariantRowsForSubmit() {
        var tbody = document.getElementById('variant-rows-tbody');
        if (!tbody || !toggle || !toggle.checked) {
            return true;
        }

        tbody.querySelectorAll('tr').forEach(function (tr) {
            setVariantRowSubmitExcluded(tr, false);
        });

        var messages = [];
        var kept = 0;

        function variantFieldFilled(val) {
            return val != null && String(val).trim() !== '';
        }

        function variantIsZero(val) {
            if (!variantFieldFilled(val)) {
                return false;
            }
            return parseFloat(val) === 0;
        }

        function shouldSkipNewVariantRow(priceVal, stockVal) {
            var priceFilled = variantFieldFilled(priceVal);
            var stockFilled = variantFieldFilled(stockVal);
            if (!priceFilled && (!stockFilled || variantIsZero(stockVal))) {
                return true;
            }
            if (priceFilled && stockFilled) {
                return false;
            }
            return true;
        }

        tbody.querySelectorAll('tr').forEach(function (tr) {
            var priceInput = tr.querySelector('input[name*="[price]"]');
            var stockInput = tr.querySelector('input[name*="[stock_quantity]"]');
            var idInput = tr.querySelector('input[name*="[id]"]');
            var hasId = idInput && String(idInput.value).trim() !== '';
            var priceVal = priceInput ? priceInput.value : '';
            var stockVal = stockInput ? stockInput.value : '';
            var priceFilled = variantFieldFilled(priceVal);
            var stockFilled = variantFieldFilled(stockVal);
            var label = variantRowLabelFromTr(tr);

            if (hasId) {
                if (!priceFilled) {
                    messages.push('Price is required for ' + label + '.');
                }
                if (!stockFilled) {
                    messages.push('Stock is required for ' + label + '.');
                }
                if (priceFilled && stockFilled) {
                    kept++;
                }
                return;
            }

            if (shouldSkipNewVariantRow(priceVal, stockVal)) {
                setVariantRowSubmitExcluded(tr, true);
                return;
            }

            kept++;
        });

        if (messages.length) {
            showVariantNotice(messages[0], true);
            return false;
        }

        if (kept < 1) {
            showVariantNotice('Add at least one variant with both price and stock filled in.', true);
            return false;
        }

        clearVariantNotice();
        return true;
    }

    document.querySelectorAll('form').forEach(function (form) {
        if (!form.querySelector('#variant-product-fields')) {
            return;
        }
        form.addEventListener('submit', function (e) {
            if (!prepareVariantRowsForSubmit()) {
                e.preventDefault();
            }
        });
    });

})();
</script>
@endpush
