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

        @if(auth()->user()->business && auth()->user()->business->usesCustomerCreditMode())
            <p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                For credit limits and receivables, use
                <a href="{{ tenant_route('tenant.customer-credit.customers.index') }}" class="font-semibold underline">Receivables → Customers</a>.
            </p>
        @endif

        <x-button variant="primary" type="submit">Save contact</x-button>
    </form>
</x-card>
@endsection
