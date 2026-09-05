@extends(auth()->user()->usesCashierExperience() ? 'layouts.cashier' : 'layouts.admin')

@section('title', $isHospitality ?? false ? 'Add Menu Item' : 'Add Product')
@section('container_class', 'max-w-4xl')

@section('content')
<x-page-header :title="$isHospitality ?? false ? 'Add Menu Item' : 'Add Product'" :subtitle="$isHospitality ?? false ? 'Food and beverages for this branch' : 'Everything inline — brands, attributes, and variants on one page'">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.inventory.index') }}">All products</x-button>
    </x-slot>
</x-page-header>

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
