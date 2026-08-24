@extends('layouts.admin')

@section('title', 'Inventory')
@section('container_class', 'max-w-4xl')

@section('content')
<x-page-header title="Inventory" subtitle="{{ $products->total() }} products in stock">
    <x-slot name="actions">
        <x-button variant="primary" size="sm" href="{{ tenant_route('tenant.inventory.create') }}">+ Add Product</x-button>
    </x-slot>
</x-page-header>

{{-- Mobile: stacked cards --}}
<div class="space-y-3 md:hidden">
    @forelse($products as $product)
        <x-card :padding="false" class="p-4">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-medium text-gray-900">{{ $product->name }}</p>
                    <p class="text-xs text-gray-500">{{ $product->sku ?? 'No SKU' }} · {{ $product->measurement_unit }}</p>
                    @if($product->variant_attributes)
                        <x-badge color="gray" class="mt-2">{{ format_variant_attributes($product->variant_attributes) }}</x-badge>
                    @endif
                </div>
                <div class="text-right shrink-0">
                    <p class="font-semibold text-gray-900">@money($product->price)</p>
                    <p class="text-xs {{ $product->stock_quantity <= 5 ? 'text-red-600 font-medium' : 'text-gray-500' }}">Stock: {{ $product->stock_quantity }}</p>
                    <a href="{{ tenant_route('tenant.inventory.edit', ['product' => $product]) }}" class="mt-1 inline-block text-xs font-medium text-indigo-600 hover:text-indigo-700">Edit</a>
                </div>
            </div>
        </x-card>
    @empty
        <x-card class="text-center text-sm text-gray-500">No products yet.</x-card>
    @endforelse
</div>

{{-- Desktop: table --}}
<x-card :padding="false" class="hidden md:block overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">SKU</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Price</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Stock</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse($products as $product)
                    <tr class="transition hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <p class="text-sm font-medium text-gray-900">{{ $product->name }}</p>
                            <p class="text-xs text-gray-500">{{ $product->measurement_unit }}</p>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $product->sku ?? '—' }}</td>
                        <td class="px-6 py-4 text-right text-sm font-medium text-gray-900">@money($product->price)</td>
                        <td class="px-6 py-4 text-right text-sm {{ $product->stock_quantity <= 5 ? 'font-medium text-red-600' : 'text-gray-500' }}">{{ $product->stock_quantity }}</td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ tenant_route('tenant.inventory.edit', ['product' => $product]) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">No products yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>

<div class="mt-6">{{ $products->links() }}</div>
@endsection
