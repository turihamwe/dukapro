@extends('layouts.admin')

@section('title', 'Add Branch')

@section('content')
<x-page-header title="Add Branch" subtitle="Create a new store location">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.branches.index') }}">All branches</x-button>
    </x-slot>
</x-page-header>

<x-card class="max-w-2xl">
    <form method="POST" action="{{ tenant_route('tenant.branches.store') }}" class="space-y-5">
        @csrf
        <x-input type="text" name="name" label="Branch name" value="{{ old('name') }}" required placeholder="e.g. Kampala Main" />
        <x-input type="text" name="address" label="Address" value="{{ old('address') }}" placeholder="Street, city" />
        <x-input type="text" name="phone" label="Phone" value="{{ old('phone') }}" />
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600" {{ old('is_active', true) ? 'checked' : '' }}>
            Active branch
        </label>
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="is_default" value="1" class="rounded border-gray-300 text-indigo-600" {{ old('is_default') ? 'checked' : '' }}>
            Set as default branch
        </label>
        <x-button variant="primary" type="submit">Save branch</x-button>
    </form>
</x-card>
@endsection
