@extends('layouts.admin')

@section('title', 'Edit Brand')
@section('container_class', 'max-w-4xl')

@section('content')
<x-page-header title="Edit Brand" subtitle="{{ $brand->name }}" />

<x-card>
    <form method="POST" action="{{ tenant_route('tenant.brands.update', ['brand' => $brand]) }}" class="space-y-6">
        @csrf @method('PUT')
        @include('brands._form', ['brand' => $brand])
        <x-button variant="primary" size="lg" type="submit">Update brand</x-button>
    </form>
</x-card>
@endsection
