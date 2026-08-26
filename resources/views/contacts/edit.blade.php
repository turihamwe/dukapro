@extends('layouts.admin')

@section('title', 'Edit Contact')

@section('content')
<x-page-header :title="'Edit ' . $customer->name" subtitle="Update contact details" />

<x-card class="max-w-2xl">
    <form method="POST" action="{{ tenant_route('tenant.contacts.update', ['customer' => $customer]) }}" class="space-y-5">
        @csrf
        @method('PUT')
        <x-input type="text" name="name" label="Full name" value="{{ old('name', $customer->name) }}" required />
        <x-input type="text" name="company_name" label="Company / Organization" value="{{ old('company_name', $customer->company_name) }}" />
        <div class="grid gap-4 sm:grid-cols-2">
            <x-input type="text" name="phone" label="Phone" value="{{ old('phone', $customer->phone) }}" />
            <x-input type="email" name="email" label="Email" value="{{ old('email', $customer->email) }}" />
        </div>
        <x-textarea name="address" label="Address" rows="2">{{ old('address', $customer->address) }}</x-textarea>
        <x-textarea name="notes" label="Notes" rows="2">{{ old('notes', $customer->notes) }}</x-textarea>

        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="is_credit_customer" value="1" class="rounded border-gray-300 text-indigo-600" {{ old('is_credit_customer', $customer->is_credit_customer) ? 'checked' : '' }}>
            Credit customer
        </label>

        <x-input type="number" step="0.01" name="credit_limit" label="Credit limit" value="{{ old('credit_limit', $customer->credit_limit) }}" />

        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600" {{ old('is_active', $customer->is_active) ? 'checked' : '' }}>
            Active
        </label>

        <div class="flex gap-3">
            <x-button variant="primary" type="submit">Save changes</x-button>
            <x-button variant="secondary" href="{{ tenant_route('tenant.contacts.show', ['customer' => $customer]) }}">Cancel</x-button>
        </div>
    </form>
</x-card>
@endsection
