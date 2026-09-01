@extends('layouts.admin')

@section('title', 'Add Brand')
@section('container_class', 'max-w-4xl')

@section('content')
<x-page-header title="Add Brand" subtitle="Create a brand your products can belong to" />

<x-card>
    <form method="POST" action="{{ tenant_route('tenant.brands.store') }}" class="space-y-6">
        @csrf
        @include('brands._form')
        <x-button variant="primary" size="lg" type="submit">Save brand</x-button>
    </form>
</x-card>
@endsection
