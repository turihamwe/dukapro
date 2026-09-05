@php
    $isEdit = isset($product);
    $isVariable = $isEdit && $product->isVariableParent();
    $selectedBrandId = old('brand_id', $product->brand_id ?? '');
    $productType = old('product_type', $isVariable ? 'variable' : 'simple');
    $variantsEnabled = $productType === 'variable';
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
        'canViewCost' => $canViewCost,
        'quickBrandUrl' => $quickBrandUrl ?? tenant_route('tenant.brands.quick-store'),
        'quickUnitUrl' => $quickUnitUrl ?? tenant_route('tenant.inventory.units.quick-store'),
        'quickAttributeUrl' => $quickAttributeUrl ?? tenant_route('tenant.inventory.attributes.quick-store'),
        'quickValueUrl' => $quickValueUrl ?? tenant_route('tenant.inventory.attributes.quick-value'),
        'catalogUrl' => $catalogUrl ?? tenant_route('tenant.inventory.catalog'),
        'attributesManageUrl' => tenant_route('tenant.inventory.attributes.index'),
    ];
@endphp

<div class="space-y-5">
    @if(!empty($requireBranch) && $branches->isNotEmpty())
        <div>
            <label for="branch_id" class="mb-1 block text-sm font-medium text-gray-700">Branch</label>
            <select name="branch_id" id="branch_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value="">Select branch for this menu item…</option>
                @foreach($branches as $branchId => $branchName)
                    <option value="{{ $branchId }}" @selected(old('branch_id') == $branchId)>{{ $branchName }}</option>
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

    {{-- Simple product fields --}}
    <div id="simple-product-fields" class="space-y-5 {{ $variantsEnabled ? 'hidden' : '' }}">
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700" for="simple_sku">SKU / item code <span class="font-normal text-gray-400">(optional)</span></label>
            <input type="text" name="sku" id="simple_sku" value="{{ old('sku', $product->sku ?? '') }}" placeholder="Leave blank to auto-generate"
                   class="simple-field block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700" for="simple_price">Selling price <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" min="0" name="price" id="simple_price" value="{{ old('price', $product->price ?? '') }}" required
                       class="simple-field block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            @if($canViewCost)
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700" for="simple_cost_price">Cost price</label>
                    <input type="number" step="0.01" min="0" name="cost_price" id="simple_cost_price" value="{{ old('cost_price', $product->cost_price ?? '') }}"
                           class="simple-field block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            @endif
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700" for="simple_stock">Stock on hand <span class="text-red-500">*</span></label>
            <input type="number" step="0.001" min="0" name="stock_quantity" id="simple_stock" value="{{ old('stock_quantity', $product->stock_quantity ?? 0) }}" required
                   class="simple-field block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
    </div>

    {{-- Variant toggle --}}
    <div class="flex items-center gap-3">
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

    @php
        $selectedUnit = old('measurement_unit', $product->measurement_unit ?? 'piece');
    @endphp
    <div class="rounded-xl border border-gray-200 bg-white p-4">
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
    </div>

    <x-input type="number" step="1" name="critical_threshold" label="Low-stock alert" value="{{ old('critical_threshold', $product->critical_threshold ?? 5) }}" />

    {{-- Variant builder --}}
    <div id="variant-product-fields" class="{{ $variantsEnabled ? '' : 'hidden' }}">
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-5 space-y-5">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">Attributes &amp; variants</h3>
                    <p class="mt-1 text-xs text-gray-500">Pick attribute values below — variant rows appear automatically.</p>
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
                        <p class="mb-2 text-sm font-medium text-gray-800">{{ $attribute->name }}</p>
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
                                    data-attribute-id="{{ $attribute->id }}">Add</button>
                        </div>
                    </div>
                @empty
                    <p id="no-attributes-msg" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">No attributes yet. Add Size or Color above, or use Manage attributes.</p>
                @endforelse
            </div>

            <div id="variant-table-wrap" class="hidden overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-white">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium text-gray-500">Variant</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-500">SKU <span class="font-normal text-gray-400">(optional)</span></th>
                            <th class="px-3 py-2 text-left font-medium text-gray-500">Price</th>
                            @if($canViewCost)
                                <th class="px-3 py-2 text-left font-medium text-gray-500">Cost</th>
                            @endif
                            <th class="px-3 py-2 text-left font-medium text-gray-500">Stock</th>
                        </tr>
                    </thead>
                    <tbody id="variant-rows-tbody" class="divide-y divide-gray-100 bg-white"></tbody>
                </table>
            </div>
        </div>
    </div>

    <x-textarea name="description" label="Notes (optional)" rows="2">{{ old('description', $product->description ?? '') }}</x-textarea>

    @if($isEdit)
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
            Active (visible in POS when in stock)
        </label>
    @endif
</div>

<script type="application/json" id="product-form-config">@json($formConfig)</script>

@push('scripts')
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
    var savedVariants = config.existingVariants || [];
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

    // --- Simple / variant mode toggle ---
    var toggle = document.getElementById('enable_variants_toggle');
    var simple = document.getElementById('simple-product-fields');
    var variant = document.getElementById('variant-product-fields');
    var typeInput = document.getElementById('product_type_input');

    function syncVariantMode() {
        if (!toggle || !simple || !variant || !typeInput) return;
        var on = toggle.checked;
        simple.classList.toggle('hidden', on);
        variant.classList.toggle('hidden', !on);
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

    if (toggle) {
        toggle.addEventListener('change', syncVariantMode);
        syncVariantMode();
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
            var attrs = variant.attribute_values || {};
            var keys = Object.keys(values);
            if (keys.length !== Object.keys(attrs).length) return false;
            return keys.every(function (k) { return String(attrs[k]) === String(values[k]); });
        });
    }

    function rebuildVariantTable() {
        var tbody = document.getElementById('variant-rows-tbody');
        var wrap = document.getElementById('variant-table-wrap');
        if (!tbody || !wrap) return;

        var combos = cartesianCombinations(getSelectedValues());
        tbody.innerHTML = '';

        if (!combos.length) {
            wrap.classList.add('hidden');
            return;
        }

        wrap.classList.remove('hidden');

        combos.forEach(function (attributeValues, index) {
            var key = comboKey(attributeValues);
            var saved = findSavedMatch(attributeValues) || {};
            var prev = rowState[key] || {};
            var label = Object.keys(attributeValues).sort().map(function (k) {
                return k + ': ' + attributeValues[k];
            }).join(' · ');

            rowState[key] = {
                sku: prev.sku != null ? prev.sku : (saved.sku || ''),
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

            tr.innerHTML =
                '<td class="px-3 py-2 text-gray-900">' + esc(label) + attrsHtml + idHtml + '</td>' +
                '<td class="px-3 py-2"><input type="text" name="variants[' + index + '][sku]" value="' + esc(rowState[key].sku) + '" placeholder="Auto" class="w-full min-w-[88px] rounded-lg border-gray-300 text-sm variant-field" data-key="' + esc(key) + '" data-field="sku"></td>' +
                '<td class="px-3 py-2"><input type="number" step="0.01" min="0" required name="variants[' + index + '][price]" value="' + esc(rowState[key].price) + '" class="w-full min-w-[80px] rounded-lg border-gray-300 text-sm variant-field" data-key="' + esc(key) + '" data-field="price"></td>' +
                (canViewCost ? '<td class="px-3 py-2"><input type="number" step="0.01" min="0" name="variants[' + index + '][cost_price]" value="' + esc(rowState[key].cost_price) + '" class="w-full min-w-[80px] rounded-lg border-gray-300 text-sm variant-field" data-key="' + esc(key) + '" data-field="cost_price"></td>' : '') +
                '<td class="px-3 py-2"><input type="number" step="0.001" min="0" required name="variants[' + index + '][stock_quantity]" value="' + esc(rowState[key].stock_quantity) + '" class="w-full min-w-[72px] rounded-lg border-gray-300 text-sm variant-field" data-key="' + esc(key) + '" data-field="stock_quantity"></td>';

            tbody.appendChild(tr);
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

    document.getElementById('attribute-picker-list').addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('variant-value-checkbox')) {
            rebuildVariantTable();
        }
    });

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
            return '<div class="attribute-picker rounded-lg border border-gray-200 bg-white p-4" data-attribute-id="' + esc(attribute.id) + '" data-attribute-name="' + esc(attribute.name) + '">' +
                '<p class="mb-2 text-sm font-medium text-gray-800">' + esc(attribute.name) + '</p>' +
                '<div class="flex flex-wrap gap-2">' + valuesHtml + '</div>' +
                '<div class="mt-3 flex gap-2">' +
                '<input type="text" class="new-attribute-value-input min-w-0 flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Add ' + esc(attribute.name) + ' option…" data-attribute-id="' + esc(attribute.id) + '">' +
                '<button type="button" class="add-attribute-value-btn shrink-0 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50" data-attribute-id="' + esc(attribute.id) + '">Add</button>' +
                '</div></div>';
        }).join('');
    }

    function refreshCatalog() {
        if (!config.catalogUrl) return Promise.resolve();
        return fetch(config.catalogUrl, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
        .then(function (res) { return res.ok ? res.json() : null; })
        .then(function (data) {
            if (data && data.attributes) renderAttributePickers(data.attributes);
        });
    }

    var addAttributeBtn = document.getElementById('add_attribute_btn');
    if (addAttributeBtn) {
        addAttributeBtn.addEventListener('click', function () {
            var nameInput = document.getElementById('new_attribute_name');
            var valuesInput = document.getElementById('new_attribute_values');
            var name = nameInput ? nameInput.value.trim() : '';
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
                body: JSON.stringify({ name: name, values_text: valuesInput ? valuesInput.value.trim() : '' }),
            })
            .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
            .then(function (result) {
                if (!result.ok) {
                    var err = result.data.errors ? Object.values(result.data.errors).flat()[0] : null;
                    throw new Error(err || result.data.message || 'Could not add attribute.');
                }
                if (nameInput) nameInput.value = '';
                if (valuesInput) valuesInput.value = '';
                return refreshCatalog();
            })
            .catch(function (e) {
                showMsg(document.getElementById('attribute_error'), e.message || 'Could not add attribute.', true);
            })
            .finally(function () { addAttributeBtn.disabled = false; });
        });
    }

    document.getElementById('attribute-picker-list').addEventListener('click', function (e) {
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
            if (input) input.value = '';
            return refreshCatalog();
        })
        .then(function () { rebuildVariantTable(); })
        .catch(function (e) { alert(e.message || 'Could not add value.'); });
    });
})();
</script>
@endpush
