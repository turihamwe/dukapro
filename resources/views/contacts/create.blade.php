@extends('layouts.admin')

@section('title', 'Add Contact')

@section('content')
<x-page-header title="Add Contact" subtitle="Save a contact to your business CRM" />

<x-card class="max-w-2xl">
    <form method="POST" action="{{ tenant_route('tenant.contacts.store') }}" class="space-y-5">
        @csrf
        <x-input type="text" name="name" label="Full name" required />
        <x-input type="text" name="company_name" label="Company / Organization" />
        <div class="grid gap-4 sm:grid-cols-2">
            <x-input type="text" name="phone" label="Phone" />
            <x-input type="email" name="email" label="Email" />
        </div>
        <x-textarea name="address" label="Address" rows="2"></x-textarea>
        <x-textarea name="notes" label="Notes" rows="2" placeholder="Internal notes about this contact"></x-textarea>

        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="is_credit_customer" value="1" class="rounded border-gray-300 text-indigo-600" {{ old('is_credit_customer') ? 'checked' : '' }}>
            Credit customer (can buy on account)
        </label>

        <x-input type="number" step="0.01" name="credit_limit" label="Credit limit" value="{{ old('credit_limit', 0) }}" />

        <x-button variant="primary" type="submit">Save contact</x-button>
    </form>
</x-card>
@endsection
