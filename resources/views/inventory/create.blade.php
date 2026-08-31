@extends(auth()->user()->usesCashierExperience() ? 'layouts.cashier' : 'layouts.admin')

@section('title', 'Add Product')
@section('container_class', 'max-w-4xl')

@section('content')
<x-page-header title="Add Product" subtitle="Each item is tracked separately with its own price and stock">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.inventory.index') }}">All products</x-button>
    </x-slot>
</x-page-header>

<div class="mb-6 rounded-xl border border-emerald-100 bg-emerald-50/80 px-4 py-3 text-sm text-emerald-900">
    <p class="font-medium">Add products individually</p>
    <p class="mt-1 text-emerald-800/90">Do not group items under one generic name. Create a separate entry for every product you sell — for example, <strong>Guinness beer</strong>, <strong>Club beer</strong>, and <strong>Bell beer</strong> should each be their own product with separate pricing and stock counts.</p>
</div>

<x-card>
    <form method="POST" action="{{ tenant_route('tenant.inventory.store') }}" class="space-y-6">
        @csrf
        @include('inventory._form')
        <div class="flex flex-wrap gap-3">
            <x-button variant="primary" size="lg" type="submit">Save product</x-button>
            <x-button variant="secondary" size="lg" type="submit" name="add_another" value="1">Save &amp; add another</x-button>
        </div>
    </form>
</x-card>
@endsection
