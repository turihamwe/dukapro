@extends('layouts.admin')

@section('title', 'Purchase Receives')
@section('container_class', 'max-w-4xl')

@section('content')
<x-page-header title="Purchase receives / restock" subtitle="Receive stock and create a supplier bill on credit">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.supplier-credit.bills.index') }}">Bills</x-button>
    </x-slot>
</x-page-header>

@if($errors->has('supplier_name'))
    <x-card class="mb-4 border-red-200 bg-red-50 text-sm text-red-800">{{ $errors->first('supplier_name') }}</x-card>
@endif

<form method="GET" class="mb-4">
    <x-input type="search" name="search" value="{{ $search ?? '' }}" placeholder="Search products…" />
</form>

<div class="space-y-4">
    @forelse($products as $product)
        @php $isVariable = $product->isVariableParent(); @endphp
        <x-card class="!p-4">
            <p class="font-semibold text-gray-900">{{ $product->name }}</p>
            <p class="text-xs text-gray-500">{{ $product->sku ?? 'No SKU' }}</p>

            @if($isVariable)
                <div class="mt-4 space-y-4 border-t border-gray-100 pt-4">
                    @foreach($product->variants as $variant)
                        <form method="POST" action="{{ tenant_route('tenant.supplier-credit.purchases.store') }}" class="grid gap-3 border-b border-gray-50 pb-4 last:border-0 last:pb-0 sm:grid-cols-2 lg:grid-cols-6 lg:items-end">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <input type="hidden" name="variant_id" value="{{ $variant->id }}">
                            <div class="lg:col-span-2">
                                <p class="text-sm font-medium text-gray-800">{{ $variant->displayName() }}</p>
                            </div>
                            <div>
                                @include('supplier-credit.purchases._supplier-field')
                            </div>
                            <div>
                                <x-input type="number" step="0.001" min="0.001" name="quantity" label="Qty" required />
                            </div>
                            <div>
                                <x-input type="number" step="0.01" min="0" name="unit_cost" label="Unit cost" value="{{ old('unit_cost', \App\Support\ProductInventoryValuation::purchaseDefaultUnitCost($variant)) }}" required />
                                @include('supplier-credit.purchases._unit-cost-hint', ['product' => $variant])
                            </div>
                            <div class="flex items-end">
                                <x-button variant="primary" size="sm" type="submit" class="w-full">Save on credit</x-button>
                            </div>
                        </form>
                    @endforeach
                </div>
            @else
                <form method="POST" action="{{ tenant_route('tenant.supplier-credit.purchases.store') }}" class="mt-4 grid gap-3 border-t border-gray-100 pt-4 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <div>
                        @include('supplier-credit.purchases._supplier-field')
                    </div>
                    <div>
                        <x-input type="number" step="0.001" min="0.001" name="quantity" label="Quantity" required />
                    </div>
                    <div>
                        <x-input type="number" step="0.01" min="0" name="unit_cost" label="Unit cost" value="{{ old('unit_cost', \App\Support\ProductInventoryValuation::purchaseDefaultUnitCost($product)) }}" required />
                        @include('supplier-credit.purchases._unit-cost-hint', ['product' => $product])
                    </div>
                    <div>
                        <x-input type="date" name="purchase_date" label="Date" value="{{ now()->toDateString() }}" />
                    </div>
                    <div class="flex items-end">
                        <x-button variant="primary" size="sm" type="submit" class="w-full">Save on credit</x-button>
                    </div>
                </form>
            @endif
        </x-card>
    @empty
        <x-card class="py-6 text-center text-sm text-gray-500">
            No products found.
            @can('create', App\Models\Product::class)
                <a href="{{ tenant_route('tenant.inventory.create') }}" class="font-medium text-indigo-600 hover:text-indigo-800">Add product</a>
            @endcan
        </x-card>
    @endforelse
</div>

<div class="mt-6">{{ $products->links() }}</div>
@endsection

@push('scripts')
<script>
(function () {
    function syncSupplierField(wrapper) {
        var select = wrapper.querySelector('.supplier-select');
        var newBox = wrapper.querySelector('.supplier-new');
        var nameInput = wrapper.querySelector('input[name="supplier_name"]');
        if (!select || !newBox) return;

        var isNew = select.value === '' || select.value === '__new__';
        newBox.classList.toggle('hidden', !isNew);
        if (nameInput) {
            nameInput.required = isNew;
            if (!isNew) {
                nameInput.value = '';
            }
        }
    }

    document.querySelectorAll('[data-supplier-field]').forEach(function (wrapper) {
        var select = wrapper.querySelector('.supplier-select');
        if (!select) return;
        select.addEventListener('change', function () {
            syncSupplierField(wrapper);
        });
        syncSupplierField(wrapper);
    });

    document.querySelectorAll('[data-supplier-field]').forEach(function (wrapper) {
        var form = wrapper.closest('form');
        if (!form || form.dataset.supplierSubmitBound) {
            return;
        }
        form.dataset.supplierSubmitBound = '1';
        form.addEventListener('submit', function () {
            var select = wrapper.querySelector('.supplier-select');
            var nameInput = wrapper.querySelector('input[name="supplier_name"]');
            if (select && (select.value === '' || select.value === '__new__')) {
                select.removeAttribute('name');
            } else if (nameInput) {
                nameInput.removeAttribute('name');
            }
        });
    });
})();
</script>
@endpush
