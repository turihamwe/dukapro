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
    <x-textarea name="variant_attributes_json" label="Variant Attributes (JSON)" rows="2" hint="Optional. e.g. size, color, model for hardware variants." placeholder='{"size":"Large","color":"Blue"}'>{{ old('variant_attributes_json', isset($product) && $product->variant_attributes ? json_encode($product->variant_attributes) : '') }}</x-textarea>
    <x-textarea name="description" label="Description" rows="2">{{ old('description', $product->description ?? '') }}</x-textarea>
    @if(isset($product))
        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
            Active
        </label>
    @endif
</div>
