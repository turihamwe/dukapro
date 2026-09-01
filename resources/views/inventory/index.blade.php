@extends(auth()->user()->usesCashierExperience() ? 'layouts.cashier' : 'layouts.admin')

@section('title', 'Inventory')
@section('container_class', 'max-w-4xl')

@section('content')
<x-page-header title="Inventory" subtitle="Each product is tracked individually with its own price and stock">
    <x-slot name="actions">
        @can('create', App\Models\Product::class)
            <x-button variant="primary" size="sm" href="{{ tenant_route('tenant.inventory.create') }}">+ Add Product</x-button>
        @endcan
    </x-slot>
</x-page-header>

<div class="mb-4">
    <label for="inventory-search" class="sr-only">Search products</label>
    <div class="relative">
        <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input type="search" id="inventory-search" name="search" value="{{ $search ?? '' }}"
               placeholder="Search by name, SKU, or notes…"
               autocomplete="off"
               class="w-full rounded-xl border border-gray-200 bg-white py-3 pl-10 pr-4 text-sm shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
    </div>
    @if(!empty($search))
        <p class="mt-2 text-xs text-gray-500">
            Showing results for “{{ $search }}”.
            <a href="{{ tenant_route('tenant.inventory.index') }}" class="font-medium text-emerald-600 hover:text-emerald-700">Clear search</a>
        </p>
    @endif
</div>

{{-- Mobile: stacked cards --}}
<div id="inventory-mobile-list" class="space-y-3 md:hidden">
    @forelse($products as $product)
        <x-card :padding="false" class="inventory-item p-4" data-search="{{ strtolower($product->name . ' ' . ($product->sku ?? '') . ' ' . $product->measurement_unit . ' ' . ($product->description ?? '')) }}">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-medium text-gray-900">{{ $product->name }}</p>
                    <p class="text-xs text-gray-500">
                        {{ $product->brand->name ?? 'No brand' }}
                        · {{ $product->sku ?? 'No SKU' }}
                        · {{ $product->measurement_unit }}
                        @if($product->variants_count > 0)
                            · {{ $product->variants_count }} variants
                        @endif
                    </p>
                    @if($product->description)
                        <p class="mt-1 line-clamp-2 text-xs text-gray-500">{{ $product->description }}</p>
                    @endif
                </div>
                <div class="text-right shrink-0">
                    <p class="text-xs text-gray-500">Sell: <span class="font-semibold text-gray-900">@money($product->price)</span></p>
                    @can('view-cost-prices')
                        <p class="text-xs text-gray-500">Cost: <span class="font-medium text-gray-700">@money($product->cost_price ?? 0)</span></p>
                    @endcan
                    <p class="text-xs {{ $product->stock_quantity <= 5 ? 'text-red-600 font-medium' : 'text-gray-500' }}">Stock: {{ $product->stock_quantity }}</p>
                    @can('update', $product)
                        <a href="{{ tenant_route('tenant.inventory.edit', ['product' => $product]) }}" class="mt-1 inline-block text-xs font-medium text-indigo-600 hover:text-indigo-700">Edit</a>
                    @endcan
                </div>
            </div>
        </x-card>
    @empty
        <x-card class="text-center text-sm text-gray-500">No products match your search.</x-card>
    @endforelse
</div>

{{-- Desktop: table --}}
<x-card :padding="false" class="hidden md:block overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Brand / SKU</th>
                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Cost (UGX)</th>
                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Sell (UGX)</th>
                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">In Stock</th>
                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500"></th>
                </tr>
            </thead>
            <tbody id="inventory-desktop-body" class="divide-y divide-gray-100 bg-white">
                @forelse($products as $product)
                    <tr class="inventory-item transition hover:bg-gray-50" data-search="{{ strtolower($product->name . ' ' . ($product->sku ?? '') . ' ' . $product->measurement_unit . ' ' . ($product->description ?? '')) }}">
                        <td class="px-6 py-4 text-left">
                            <p class="text-sm font-medium text-gray-900">{{ $product->name }}</p>
                            @if($product->description)
                                <p class="mt-0.5 line-clamp-1 text-xs text-gray-500">{{ $product->description }}</p>
                            @endif
                            @if($product->variants_count > 0)
                                <p class="mt-1 text-xs font-medium text-indigo-600">{{ $product->variants_count }} sellable variants</p>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $product->brand->name ?? '—' }}
                            @if($product->variants_count === 0)
                                <span class="block text-xs text-gray-400">{{ $product->sku ?? '—' }} · {{ $product->measurement_unit }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center text-sm text-gray-600">
                            @can('view-cost-prices')
                                @money($product->cost_price ?? 0)
                            @else
                                —
                            @endcan
                        </td>
                        <td class="px-6 py-4 text-center text-sm font-medium text-gray-900">
                            @if($product->variants_count > 0)
                                —
                            @else
                                @money($product->price)
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center text-sm {{ $product->variants_count > 0 ? 'text-gray-500' : ($product->stock_quantity <= 5 ? 'font-medium text-red-600' : 'text-gray-500') }}">
                            @if($product->variants_count > 0)
                                {{ $product->variants->sum('stock_quantity') }} total
                            @else
                                {{ $product->stock_quantity }}
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            @can('update', $product)
                                <a href="{{ tenant_route('tenant.inventory.edit', ['product' => $product]) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">Edit</a>
                            @endcan
                            @can('delete', $product)
                                <form method="POST" action="{{ tenant_route('tenant.inventory.destroy', ['product' => $product]) }}" class="ml-3 inline" onsubmit="return confirm('Delete this product?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-700">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">No products match your search.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>

<div class="mt-6">{{ $products->links() }}</div>
@endsection

@push('scripts')
<script>
(function () {
    var input = document.getElementById('inventory-search');
    if (!input) return;

    var baseUrl = @json(tenant_route('tenant.inventory.index'));
    var timer = null;

    function filterVisible(query) {
        var q = query.toLowerCase().trim();
        document.querySelectorAll('.inventory-item').forEach(function (el) {
            var hay = el.getAttribute('data-search') || '';
            el.classList.toggle('hidden', q !== '' && hay.indexOf(q) === -1);
        });
    }

    input.addEventListener('input', function () {
        var value = input.value;
        filterVisible(value);
        clearTimeout(timer);
        timer = setTimeout(function () {
            var trimmed = value.trim();
            var target = baseUrl + (trimmed ? '?search=' + encodeURIComponent(trimmed) : '');
            var current = window.location.pathname + window.location.search;
            var next = new URL(target, window.location.origin).pathname + new URL(target, window.location.origin).search;
            if (current !== next && (trimmed.length >= 2 || trimmed === '')) {
                window.location.href = target;
            }
        }, 500);
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(timer);
            var value = input.value.trim();
            window.location.href = baseUrl + (value ? '?search=' + encodeURIComponent(value) : '');
        }
    });
})();
</script>
@endpush
