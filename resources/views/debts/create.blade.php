@extends('layouts.admin')

@section('title', 'Add Customer')
@section('container_class', 'max-w-4xl')

@section('content')
<x-page-header title="Add Credit Customer" subtitle="Register a hardware buyer for credit sales" />

<x-card>
    <form method="POST" action="{{ tenant_route('tenant.debts.store') }}" class="space-y-5">
        @csrf
        <x-input type="text" name="name" label="Name" required />
        <x-input type="text" name="phone" label="Phone" />
        <x-input type="email" name="email" label="Email" />
        <x-textarea name="address" label="Address" rows="2"></x-textarea>
        <x-input type="number" step="0.01" name="credit_limit" label="Credit Limit" required />
        <x-button variant="primary" size="lg" type="submit">Save Customer</x-button>
    </form>
</x-card>
@endsection
