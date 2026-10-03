@php
    $suppliersEmpty = $suppliers->isEmpty();
@endphp
<div class="supplier-field" data-supplier-field>
    <label class="mb-1 block text-xs font-medium text-gray-600">Supplier</label>
    <select name="supplier_id" class="supplier-select w-full rounded-lg border-gray-300 text-sm" autocomplete="off">
        <option value="" @selected($suppliersEmpty)>{{ $suppliersEmpty ? 'Add new supplier…' : 'Select supplier…' }}</option>
        @foreach($suppliers as $supplier)
            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
        @endforeach
        @unless($suppliersEmpty)
            <option value="__new__">+ Add new supplier…</option>
        @endunless
    </select>
    <div class="supplier-new mt-2 {{ $suppliersEmpty ? '' : 'hidden' }}">
        <input type="text" name="supplier_name" maxlength="255" placeholder="Supplier name"
               class="w-full rounded-lg border-gray-300 text-sm"
               value="{{ old('supplier_name') }}">
        <p class="mt-1 text-xs text-gray-500">You can add phone and other details later under Suppliers.</p>
    </div>
</div>
