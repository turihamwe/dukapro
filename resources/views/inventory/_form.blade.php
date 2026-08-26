<div class="space-y-5">
    <x-input type="text" name="name" label="Name" value="{{ old('name', $product->name ?? '') }}" required />
    <x-input type="text" name="sku" label="SKU" value="{{ old('sku', $product->sku ?? '') }}" />
    <div class="grid gap-5 sm:grid-cols-2">
        <x-input type="number" step="0.01" name="price" label="Price" value="{{ old('price', $product->price ?? '') }}" required />
        @can('view-cost-prices')
            <x-input type="number" step="0.01" name="cost_price" label="Cost Price" value="{{ old('cost_price', $product->cost_price ?? '') }}" />
        @endcan
    </div>
    <x-select name="measurement_unit" label="Measurement Unit">
        @foreach(['piece','kg','g','liter','ml','box','pack','dozen','meter'] as $unit)
            <option value="{{ $unit }}" {{ old('measurement_unit', $product->measurement_unit ?? 'piece') === $unit ? 'selected' : '' }}>{{ $unit }}</option>
        @endforeach
    </x-select>
    <x-input type="number" step="0.001" name="stock_quantity" label="Stock Quantity" value="{{ old('stock_quantity', $product->stock_quantity ?? 0) }}" required />
    <x-input type="number" step="1" name="critical_threshold" label="Critical Threshold" value="{{ old('critical_threshold', $product->critical_threshold ?? 5) }}" hint="Low-stock alert level for this product (used in inventory charts)." required />

    @php
        $variantForm = variant_attributes_for_form(
            isset($product) && old('variant_attribute_name') === null && old('variant_attribute_values') === null
                ? ($product->variant_attributes ?? null)
                : null
        );
        $variantName = old('variant_attribute_name', $variantForm['name']);
        $variantValues = old('variant_attribute_values', $variantForm['values']);
    @endphp

    <div class="rounded-xl border border-gray-200 bg-gray-50 p-5">
        <h3 class="text-sm font-semibold text-gray-900">Variant Attributes</h3>
        <p class="mt-1 text-xs text-gray-500">Optional. Describe size, color, grade, or other variant details for this product.</p>
        <div class="mt-4 space-y-4">
            <x-input
                type="text"
                name="variant_attribute_name"
                label="Attribute Name"
                value="{{ $variantName }}"
                placeholder="e.g. Size, Color, Material, Grade"
            />
            <x-input
                type="text"
                name="variant_attribute_values"
                label="Attribute Values"
                value="{{ $variantValues }}"
                placeholder="Small, Medium, Large"
                hint="Enter values separated by commas, e.g. Small, Medium, Large or Red, Blue, Black"
            />
        </div>
    </div>

    <x-textarea name="description" label="Description" rows="2">{{ old('description', $product->description ?? '') }}</x-textarea>
    @if(isset($product))
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
            Active
        </label>
    @endif
</div>
