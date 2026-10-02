@extends('layouts.admin')

@section('title', 'Product Attributes')
@section('container_class', 'max-w-4xl')

@section('content')
<x-page-header title="Product Attributes" subtitle="Define variant options for boutique inventory (sizes, colors, etc.)">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.inventory.create') }}">Add product</x-button>
    </x-slot>
</x-page-header>

<div class="mb-6 space-y-3">
    <div class="rounded-xl border border-indigo-100 bg-indigo-50/80 px-4 py-3 text-sm text-indigo-900">
        Attributes like <strong>Size</strong> or <strong>Color</strong> are option lists for variable products — not separate products. Each ticked combination on the product form becomes its own SKU in POS.
    </div>
    <div class="rounded-xl border border-amber-100 bg-amber-50/80 px-4 py-3 text-sm text-amber-950">
        <strong>Adding another size or color?</strong> Edit the existing attribute’s <strong>Values</strong> field below (e.g. change <code class="rounded bg-white/80 px-1">S, M, L</code> to <code class="rounded bg-white/80 px-1">S, M, L, XL</code>) and click <strong>Save</strong>. Then open the product → <strong>Edit</strong>, turn on variants if needed, <strong>tick the new option</strong>, set price/stock, and update. There is no separate “add variant product” button.
    </div>
</div>

<x-card class="mb-6">
    <h2 class="text-sm font-semibold text-gray-900">Add attribute</h2>
    <form method="POST" action="{{ tenant_route('tenant.inventory.attributes.store') }}" class="mt-4 space-y-4">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <x-input type="text" name="name" label="Attribute name" value="{{ old('name') }}" required placeholder="e.g. Size, Color" />
            <x-input type="text" name="values" label="Values" value="{{ old('values') }}" required placeholder="S, M, L, XL" hint="Comma-separated list of options." />
        </div>
        <x-button variant="primary" type="submit">Add attribute</x-button>
    </form>
</x-card>

<div class="space-y-4">
    @forelse($attributes as $attribute)
        <x-card>
            <form method="POST" action="{{ tenant_route('tenant.inventory.attributes.update', ['attribute' => $attribute]) }}" class="space-y-4">
                @csrf @method('PUT')
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-input type="text" name="name" label="Attribute name" value="{{ old('name', $attribute->name) }}" required />
                    <x-input type="text" name="values" label="Values" value="{{ old('values', $attribute->values->pluck('value')->implode(', ')) }}" required />
                </div>
                <div class="flex flex-wrap gap-2">
                    @can('update-inventory')
                        <x-button variant="primary" size="sm" type="submit">Save</x-button>
                    @endcan
                </div>
            </form>
            @can('delete-inventory')
                <form method="POST" action="{{ tenant_route('tenant.inventory.attributes.destroy', ['attribute' => $attribute]) }}" class="mt-3" onsubmit="return confirm('Remove this attribute?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-700">Delete attribute</button>
                </form>
            @endcan
        </x-card>
    @empty
        <x-card class="text-center text-sm text-gray-500">No attributes yet. Add Size, Color, or other options above.</x-card>
    @endforelse
</div>
@endsection
