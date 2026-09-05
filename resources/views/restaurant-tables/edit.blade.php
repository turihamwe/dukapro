@extends('layouts.admin')

@section('title', 'Edit Table')

@section('content')
<x-page-header title="Edit {{ $restaurantTable->name }}" subtitle="Update table details">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.restaurant-tables.index') }}">All tables</x-button>
    </x-slot>
</x-page-header>

<x-card class="max-w-xl">
    <form method="POST" action="{{ tenant_route('tenant.restaurant-tables.update', ['restaurantTable' => $restaurantTable]) }}" class="space-y-4">
        @csrf
        @method('PUT')
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Branch</label>
            <select name="branch_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @foreach($branches as $id => $name)
                    <option value="{{ $id }}" @selected(old('branch_id', $restaurantTable->branch_id) == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <x-input type="text" name="name" label="Table name" value="{{ old('name', $restaurantTable->name) }}" required />
        <x-input type="text" name="code" label="Short code (optional)" value="{{ old('code', $restaurantTable->code) }}" />
        <x-input type="number" name="capacity" label="Seats (optional)" value="{{ old('capacity', $restaurantTable->capacity) }}" min="1" max="99" />
        <x-input type="number" name="sort_order" label="Sort order" value="{{ old('sort_order', $restaurantTable->sort_order) }}" min="0" />
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600" {{ old('is_active', $restaurantTable->is_active) ? 'checked' : '' }}>
            Active
        </label>
        <x-button variant="primary" type="submit">Save changes</x-button>
    </form>
</x-card>
@endsection
