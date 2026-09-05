@extends('layouts.admin')

@section('title', 'Add Table')

@section('content')
<x-page-header title="Add Table" subtitle="Assign to a branch">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.restaurant-tables.index') }}">All tables</x-button>
    </x-slot>
</x-page-header>

<x-card class="max-w-xl">
    <form method="POST" action="{{ tenant_route('tenant.restaurant-tables.store') }}" class="space-y-4">
        @csrf
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Branch</label>
            <select name="branch_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @foreach($branches as $id => $name)
                    <option value="{{ $id }}" @selected(old('branch_id') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <x-input type="text" name="name" label="Table name" placeholder="Table 4" required />
        <x-input type="text" name="code" label="Short code (optional)" placeholder="T4" />
        <x-input type="number" name="capacity" label="Seats (optional)" min="1" max="99" />
        <x-input type="number" name="sort_order" label="Sort order" value="{{ old('sort_order', 0) }}" min="0" />
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-indigo-600">
            Active
        </label>
        <x-button variant="primary" type="submit">Save table</x-button>
    </form>
</x-card>
@endsection
