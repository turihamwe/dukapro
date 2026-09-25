@extends(auth()->user()->usesCashierExperience() ? 'layouts.cashier' : 'layouts.admin')

@section('title', 'Downloads & App Install')

@section('content')
<x-page-header title="Downloads" subtitle="Install {{ platform_brand('name') }} on tills, phones, and back-office devices">
    @if(auth()->user()->usesCashierExperience())
        <x-slot name="actions">
            <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.operations.index') }}">← Operations</x-button>
        </x-slot>
    @endif
</x-page-header>

@include('layouts.partials.pwa-downloads-panel')
@endsection
