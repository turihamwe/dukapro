@extends('layouts.admin')

@section('title', 'Add Product')
@section('container_class', 'max-w-4xl')

@section('content')
<x-page-header title="Add Product" subtitle="Create a new inventory item" />

<x-card>
    <form method="POST" action="{{ tenant_route('tenant.inventory.store') }}" class="space-y-6">
        @csrf
        @include('inventory._form')
        <x-button variant="primary" size="lg" type="submit">Save Product</x-button>
    </form>
</x-card>
@endsection
