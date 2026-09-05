@extends('layouts.admin')

@section('title', 'Restaurant Tables')

@section('content')
<x-page-header title="Restaurant Tables" subtitle="Optional seating for dine-in service">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.business.edit') }}">Settings</x-button>
        <x-button variant="primary" size="sm" href="{{ tenant_route('tenant.restaurant-tables.create') }}">+ Add Table</x-button>
    </x-slot>
</x-page-header>

<x-card :padding="false" class="overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Table</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Branch</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Capacity</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse($tables as $table)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $table->displayLabel() }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $table->branch->name ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $table->capacity ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $table->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $table->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right text-sm">
                            <a href="{{ tenant_route('tenant.restaurant-tables.edit', ['restaurantTable' => $table]) }}" class="font-medium text-indigo-600 hover:text-indigo-700">Edit</a>
                            <form method="POST" action="{{ tenant_route('tenant.restaurant-tables.destroy', ['restaurantTable' => $table]) }}" class="ml-3 inline" onsubmit="return confirm('Remove this table?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="font-medium text-red-600 hover:text-red-700">Remove</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">No tables yet. Enable “Use restaurant tables” in Business settings, then add tables here.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>
@endsection
