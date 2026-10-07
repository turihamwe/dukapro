@extends('layouts.admin')

@section('title', 'Credit sale #' . ($sale->sale_number ?? $sale->id))

@section('content')
<x-page-header :title="'Credit sale #' . ($sale->sale_number ?? $sale->id)" :subtitle="optional($sale->customer)->name">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.customer-credit.invoices.index') }}">All invoices</x-button>
        @can('view-sales-documents')
            <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.sales.documents') }}?q={{ urlencode($sale->sale_number ?? '') }}">Documents</x-button>
        @endcan
    </x-slot>
</x-page-header>

<div class="mb-6 grid gap-4 sm:grid-cols-3">
    <x-stat-card label="Total" :value="format_money($sale->total_amount)" accent="indigo" />
    <x-stat-card label="Date" :value="$sale->created_at->format('M d, Y H:i')" accent="gray" />
    <x-stat-card label="Cashier" :value="optional($sale->user)->name ?? '—'" accent="gray" />
</div>

<x-card class="mb-6">
    <h2 class="mb-3 text-sm font-semibold text-gray-900">Line items</h2>
    <ul class="divide-y divide-gray-100 text-sm">
        @foreach($sale->items as $item)
            <li class="flex justify-between py-2">
                <span>{{ optional($item->product)->displayName() ?? 'Item' }} × {{ $item->quantity }}</span>
                <span class="font-medium">@money($item->subtotal)</span>
            </li>
        @endforeach
    </ul>
</x-card>

@if($ledgerEntries->isNotEmpty())
    <h2 class="mb-3 text-sm font-semibold text-gray-900">Receivable ledger entries</h2>
    <div class="space-y-2">
        @foreach($ledgerEntries as $entry)
            <x-card :padding="false" class="p-3 text-sm">
                {{ $entry->description }} · @money($entry->amount)
            </x-card>
        @endforeach
    </div>
@endif

<div class="mt-6">
    @include('customer-credit.partials.delete-button', [
        'action' => tenant_route('tenant.customer-credit.invoices.destroy', ['sale' => $sale]),
        'confirm' => 'Remove this credit invoice from active records? Inventory stock will NOT be restored.',
        'label' => 'Delete invoice',
    ])
</div>
@endsection
