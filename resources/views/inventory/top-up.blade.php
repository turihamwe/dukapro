@extends(auth()->user()->usesCashierExperience() ? 'layouts.cashier' : 'layouts.admin')

@section('title', 'Top-up Stock')
@section('container_class', 'max-w-3xl')

@section('content')
@include('layouts.partials.cashier-operations-back')

<x-page-header title="Top-up Stock" subtitle="Increase quantities for products already in your catalog">
    <x-slot name="actions">
        @can('create', App\Models\Product::class)
            <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.inventory.create') }}">+ Add New Product</x-button>
        @endcan
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.inventory.index') }}">All products</x-button>
    </x-slot>
</x-page-header>

<form method="GET" class="mb-4">
    <x-input type="search" name="search" value="{{ $search ?? '' }}" placeholder="Search product name or SKU…" />
</form>

<div class="space-y-3">
    @forelse($products as $product)
        @php
            $isVariable = $product->isVariableParent();
            $available = $isVariable
                ? $product->variants->sum(fn ($v) => app(\App\Services\ProductBatchService::class)->availableStock($v))
                : app(\App\Services\ProductBatchService::class)->availableStock($product);
        @endphp
        <x-card class="!p-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-semibold text-gray-900">{{ $product->name }}</p>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ $product->sku ?? 'No SKU' }}
                        · {{ $product->branch->name ?? 'Branch' }}
                        · In stock: {{ format_unit_quantity($available, $product->measurement_unit, $business->id) }}
                    </p>
                </div>
            </div>

            @if($isVariable)
                <div class="mt-4 space-y-3 border-t border-gray-100 pt-4">
                    @foreach($product->variants as $variant)
                        @php $variantStock = app(\App\Services\ProductBatchService::class)->availableStock($variant); @endphp
                        <form method="POST" action="{{ tenant_route('tenant.inventory.top-up.store') }}" class="flex flex-wrap items-end gap-3">
                            @csrf
                            @if($search)
                                <input type="hidden" name="search" value="{{ $search }}">
                            @endif
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <input type="hidden" name="variant_id" value="{{ $variant->id }}">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-800">{{ $variant->displayName() }}</p>
                                <p class="text-xs text-gray-500">Current: {{ format_unit_quantity($variantStock, $product->measurement_unit, $business->id) }}</p>
                            </div>
                            <div class="w-36">
                                <x-input type="number" step="0.001" min="0.001" name="quantity" label="Add quantity" required />
                            </div>
                            <x-button variant="primary" size="sm" type="submit">Top up</x-button>
                        </form>
                    @endforeach
                </div>
            @else
                <form method="POST" action="{{ tenant_route('tenant.inventory.top-up.store') }}" class="mt-4 flex flex-wrap items-end gap-3 border-t border-gray-100 pt-4">
                    @csrf
                    @if($search)
                        <input type="hidden" name="search" value="{{ $search }}">
                    @endif
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <div class="w-full max-w-xs">
                        <x-input type="number" step="0.001" min="0.001" name="quantity" label="Quantity to add" placeholder="e.g. 95" required />
                    </div>
                    <x-button variant="primary" size="sm" type="submit">Top up stock</x-button>
                </form>
            @endif
        </x-card>
    @empty
        <x-card class="text-center text-sm text-gray-500">
            No products found. <a href="{{ tenant_route('tenant.inventory.create') }}" class="font-medium text-emerald-600">Add a new product</a> first.
        </x-card>
    @endforelse
</div>

<div class="mt-6">{{ $products->links() }}</div>
@endsection
