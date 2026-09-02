@extends(auth()->user()->usesCashierExperience() ? 'layouts.cashier' : 'layouts.admin')

@section('title', $product->name)
@section('container_class', 'max-w-4xl')

@section('content')
@php
    $isVariable = $product->isVariableParent();
    $sellableTarget = $isVariable ? null : $product;
@endphp

<x-page-header :title="$product->name" subtitle="Product details and batch inventory">
    <x-slot name="actions">
        @can('update', $product)
            <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.inventory.edit', ['product' => $product]) }}">Edit Product</x-button>
            @if(! $isVariable)
                <x-button variant="primary" size="sm" type="button" onclick="openAppModal('add-batch-modal')">+ Add New Batch</x-button>
            @endif
        @endcan
    </x-slot>
</x-page-header>

@if(session('success'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
@endif
@if(session('info'))
    <div class="mb-4 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">{{ session('info') }}</div>
@endif

<x-card class="mb-6">
    <dl class="grid gap-4 sm:grid-cols-2">
        <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Brand</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ $product->brand->name ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">SKU</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ $product->sku ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Sold by</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ $product->measurement_unit }}</dd>
        </div>
        <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Status</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ $product->is_active ? 'Active' : 'Inactive' }}</dd>
        </div>
        @if(! $isVariable)
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Legacy stock</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ format_unit_quantity($product->stock_quantity, $product->measurement_unit, $product->business_id) }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Default sell price</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900">@money($product->price)</dd>
            </div>
            @can('view-cost-prices')
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Default cost price</dt>
                    <dd class="mt-1 text-sm text-gray-900">@money($product->cost_price ?? 0)</dd>
                </div>
            @endcan
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Total available</dt>
                <dd class="mt-1 text-sm font-semibold text-indigo-700">{{ format_unit_quantity($product->totalStockQuantity(), $product->measurement_unit, $product->business_id) }}</dd>
            </div>
        @endif
    </dl>
    @if($product->description)
        <p class="mt-4 text-sm text-gray-600">{{ $product->description }}</p>
    @endif
</x-card>

@if($isVariable)
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900">Variants &amp; batches</h2>
    </div>
    <div class="space-y-4">
        @foreach($product->variants as $variant)
            @include('inventory.partials.batch-panel', [
                'sellable' => $variant,
                'parentProduct' => $product,
                'canViewCost' => $canViewCost,
            ])
        @endforeach
    </div>
@else
    @include('inventory.partials.batch-panel', [
        'sellable' => $product,
        'parentProduct' => $product,
        'canViewCost' => $canViewCost,
        'showLegacyRow' => true,
    ])
@endif

@can('update', $product)
    @if(! $isVariable)
        <div id="add-batch-modal" class="app-modal-overlay" role="dialog" aria-modal="true">
            <div class="app-modal-panel">
                <div class="app-modal-header">
                    <h3 class="text-lg font-semibold text-gray-900">Add New Batch</h3>
                    <button type="button" onclick="closeAppModal('add-batch-modal')" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100">&times;</button>
                </div>
                <form method="POST" action="{{ tenant_route('tenant.inventory.batches.store', ['product' => $product]) }}" class="flex min-h-0 flex-1 flex-col">
                    @csrf
                    <div class="app-modal-body space-y-4">
                        <p class="text-sm text-gray-500">Log a new shipment without changing existing legacy stock.</p>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700" for="batch_quantity">Quantity received</label>
                            <input type="number" step="0.001" min="0.001" name="quantity" id="batch_quantity" required
                                   class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700" for="batch_selling_price">Selling price</label>
                                <input type="number" step="0.01" min="0" name="selling_price" id="batch_selling_price" required
                                       value="{{ old('selling_price', $product->price) }}"
                                       class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            @can('view-cost-prices')
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700" for="batch_cost_price">Cost price</label>
                                    <input type="number" step="0.01" min="0" name="cost_price" id="batch_cost_price"
                                           value="{{ old('cost_price', $product->cost_price) }}"
                                           class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            @endcan
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700" for="batch_received_at">Received date</label>
                            <input type="date" name="received_at" id="batch_received_at" value="{{ old('received_at', now()->toDateString()) }}"
                                   class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700" for="batch_notes">Notes <span class="font-normal text-gray-400">(optional)</span></label>
                            <textarea name="notes" id="batch_notes" rows="2" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                    <div class="app-modal-footer">
                        <button type="button" onclick="closeAppModal('add-batch-modal')"
                                class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save Batch</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endcan
@endsection
