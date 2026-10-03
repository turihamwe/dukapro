@php
    $supplier = $supplier ?? null;
@endphp
<x-input type="text" name="name" label="Supplier name" value="{{ old('name', $supplier->name ?? '') }}" required />
<div class="grid gap-4 sm:grid-cols-2">
    <x-input type="text" name="phone" label="Phone" value="{{ old('phone', $supplier->phone ?? '') }}" />
    <x-input type="email" name="email" label="Email" value="{{ old('email', $supplier->email ?? '') }}" />
</div>
<x-textarea name="notes" label="Notes" rows="2">{{ old('notes', $supplier->notes ?? '') }}</x-textarea>
@if($supplier)
    <label class="flex items-center gap-2 text-sm text-gray-700">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
               @checked(old('is_active', $supplier->is_active))>
        Active (show in restock supplier lists)
    </label>
@endif
