@extends('layouts.app')

@section('title', 'Edit Product')
@section('container_class', 'max-w-4xl')

@section('content')
<x-page-header title="Edit Product" subtitle="{{ $product->name }}" />

<x-card>
    <form method="POST" action="{{ tenant_route('tenant.inventory.update', ['product' => $product]) }}" class="space-y-6">
        @csrf @method('PUT')
        @include('inventory._form', ['product' => $product])
        <x-button variant="primary" size="lg" type="submit">Update Product</x-button>
    </form>
</x-card>
@endsection
