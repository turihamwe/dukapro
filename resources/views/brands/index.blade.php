@extends(auth()->user()->usesCashierExperience() ? 'layouts.cashier' : 'layouts.admin')

@section('title', 'Brands')
@section('container_class', 'max-w-4xl')

@section('content')
<x-page-header title="Brands" subtitle="Manage product brands for your store">
    <x-slot name="actions">
        @can('create', App\Models\Brand::class)
            <x-button variant="primary" size="sm" href="{{ tenant_route('tenant.brands.create') }}">+ Add Brand</x-button>
        @endcan
    </x-slot>
</x-page-header>

<div class="mb-4">
    <form method="GET" action="{{ tenant_route('tenant.brands.index') }}" class="relative">
        <input type="search" name="search" value="{{ $search ?? '' }}" placeholder="Search brands…"
               class="w-full rounded-xl border border-gray-200 bg-white py-3 pl-4 pr-4 text-sm shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
    </form>
</div>

<x-card :padding="false" class="overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Brand</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Products</th>
                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse($brands as $brand)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <p class="text-sm font-medium text-gray-900">{{ $brand->name }}</p>
                            @if($brand->description)
                                <p class="mt-0.5 text-xs text-gray-500">{{ $brand->description }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $brand->products_count }}</td>
                        <td class="px-6 py-4 text-center">
                            <x-badge :color="$brand->is_active ? 'green' : 'gray'">{{ $brand->is_active ? 'Active' : 'Hidden' }}</x-badge>
                        </td>
                        <td class="px-6 py-4 text-right">
                            @can('update', $brand)
                                <a href="{{ tenant_route('tenant.brands.edit', ['brand' => $brand]) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">Edit</a>
                            @endcan
                            @can('delete', $brand)
                                <form method="POST" action="{{ tenant_route('tenant.brands.destroy', ['brand' => $brand]) }}" class="ml-3 inline" onsubmit="return confirm('Delete this brand? Products will be unassigned.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-700">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500">No brands yet. Add brands like Club, Bell, Guinness, or cement suppliers.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>

<div class="mt-6">{{ $brands->links() }}</div>
@endsection
