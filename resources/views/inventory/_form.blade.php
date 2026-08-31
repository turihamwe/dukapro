<div class="space-y-5">
    <x-input
        type="text"
        name="name"
        label="Product name"
        value="{{ old('name', $product->name ?? '') }}"
        required
        autofocus
        hint="Use the exact item you sell. Add each brand or size as its own product — e.g. Guinness beer, Club beer, Bell beer."
        placeholder="e.g. Guinness beer 500ml"
    />
    <x-input
        type="text"
        name="sku"
        label="SKU / item code"
        value="{{ old('sku', $product->sku ?? '') }}"
        hint="Optional unique code for this product. Helps with search and receipts."
        placeholder="e.g. GUIN-500"
    />
    <div class="grid gap-5 sm:grid-cols-2">
        <x-input type="number" step="0.01" name="price" label="Selling price" value="{{ old('price', $product->price ?? '') }}" required />
        @can('view-cost-prices')
            <x-input type="number" step="0.01" name="cost_price" label="Cost price" value="{{ old('cost_price', $product->cost_price ?? '') }}" />
        @endcan
    </div>
    <x-select name="measurement_unit" label="Sold by">
        @foreach(\App\Enums\MeasurementUnit::all() as $unit)
            <option value="{{ $unit }}" {{ old('measurement_unit', $product->measurement_unit ?? 'piece') === $unit ? 'selected' : '' }}>{{ ucfirst($unit) }}</option>
        @endforeach
    </x-select>
    <x-input type="number" step="0.001" name="stock_quantity" label="Stock on hand" value="{{ old('stock_quantity', $product->stock_quantity ?? 0) }}" required />
    <x-input type="number" step="1" name="critical_threshold" label="Low-stock alert" value="{{ old('critical_threshold', $product->critical_threshold ?? 5) }}" hint="Notify when stock falls to this level." />
    <x-textarea name="description" label="Notes (optional)" rows="2" hint="Extra details for your team — not used for grouping or variants.">{{ old('description', $product->description ?? '') }}</x-textarea>
    @if(isset($product))
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
            Active (visible in POS when in stock)
        </label>
    @endif
</div>
