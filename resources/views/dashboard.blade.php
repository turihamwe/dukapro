@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<x-page-header title="Dashboard" subtitle="Overview of today's business activity" />

<div class="grid grid-cols-2 gap-4 lg:grid-cols-3">
    @can('manage-inventory')
        <x-stat-card label="Products" :value="$stats['products']" accent="indigo" />
    @endcan
    <x-stat-card label="Today's Sales" :value="format_money($stats['today_sales'])" accent="emerald" />
    @can('view-analytics')
        <x-stat-card label="Outstanding Credit" :value="format_money($stats['outstanding_debt'])" accent="amber" class="col-span-2 lg:col-span-1" />
    @endcan
</div>

<x-card class="mt-6 space-y-3">
    @can('access-pos')
        <x-button variant="primary" size="lg" href="{{ tenant_route('tenant.pos.index') }}">Open POS Checkout</x-button>
    @endcan
    @can('manage-inventory')
        <x-button variant="secondary" size="lg" href="{{ tenant_route('tenant.inventory.create') }}">Add Product</x-button>
    @endcan
    @can('submit-reconciliation')
        <x-button variant="secondary" size="lg" href="{{ tenant_route('tenant.reconciliation.create') }}">End-of-Day Reconciliation</x-button>
    @endcan
</x-card>

@if($business->subscription_status !== 'active' && auth()->user()->can('manage-billing'))
    <x-alert type="info" class="mt-6">
        Subscription: <strong>{{ ucfirst($business->subscription_status) }}</strong>
        @if($business->trial_ends_at)
            · Trial ends {{ $business->trial_ends_at->format('M d, Y') }}
        @endif
    </x-alert>
@endif
@endsection
