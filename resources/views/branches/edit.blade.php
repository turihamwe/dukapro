@extends('layouts.admin')

@section('title', 'Edit Branch')

@section('content')
<x-page-header :title="'Edit ' . $branch->name" subtitle="Update branch details">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.branches.index') }}">All branches</x-button>
    </x-slot>
</x-page-header>

<x-card class="max-w-2xl">
    <form method="POST" action="{{ tenant_route('tenant.branches.update', ['branch' => $branch]) }}" class="space-y-5">
        @csrf
        @method('PUT')
        <x-input type="text" name="name" label="Branch name" value="{{ old('name', $branch->name) }}" required />
        <x-input type="text" name="address" label="Address" value="{{ old('address', $branch->address) }}" />
        <x-input type="text" name="phone" label="Phone" value="{{ old('phone', $branch->phone) }}" />
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600" {{ old('is_active', $branch->is_active) ? 'checked' : '' }}>
            Active branch
        </label>
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="is_default" value="1" class="rounded border-gray-300 text-indigo-600" {{ old('is_default', $branch->is_default) ? 'checked' : '' }}>
            Default branch
        </label>
        <x-button variant="primary" type="submit">Save changes</x-button>
    </form>
</x-card>
@endsection
