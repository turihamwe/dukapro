@extends('layouts.admin')

@section('title', 'Edit ' . $customer->name)

@section('content')
<x-page-header :title="$customer->name" subtitle="Edit credit customer">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.customer-credit.customers.index') }}">All customers</x-button>
    </x-slot>
</x-page-header>

<x-card class="max-w-2xl">
    <form method="POST" action="{{ tenant_route('tenant.customer-credit.customers.update', ['customer' => $customer]) }}" class="space-y-4">
        @csrf
        @method('PUT')
        @include('customer-credit.customers._form', ['customer' => $customer])
        <div class="flex gap-2">
            <x-button variant="primary" type="submit">Save changes</x-button>
            <x-button variant="secondary" href="{{ tenant_route('tenant.customer-credit.customers.index') }}">Cancel</x-button>
        </div>
    </form>
</x-card>
@endsection
