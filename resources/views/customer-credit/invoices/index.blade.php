@extends('layouts.admin')

@section('title', 'Credit Invoices')

@section('content')
<x-page-header title="Invoices / Credit Sales" subtitle="POS and on-account sales awaiting payment">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.customer-credit.customers.index') }}">Customers</x-button>
    </x-slot>
</x-page-header>

<x-stat-card class="mb-4" label="Total outstanding (all credit customers)" :value="format_money($totalReceivable)" accent="amber" />

<div class="mb-4 flex gap-2">
    <a href="{{ tenant_route('tenant.customer-credit.invoices.index') }}"
       class="rounded-full border px-3 py-1 text-xs font-medium {{ ! $status ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-200 bg-white text-gray-700' }}">All</a>
    <a href="{{ tenant_route('tenant.customer-credit.invoices.index', ['status' => 'open']) }}"
       class="rounded-full border px-3 py-1 text-xs font-medium {{ $status === 'open' ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-200 bg-white text-gray-700' }}">With balance</a>
</div>

<div class="space-y-3">
    @forelse($sales as $sale)
        <x-card :padding="false" class="p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-semibold text-gray-900">
                        <a href="{{ tenant_route('tenant.customer-credit.invoices.show', ['sale' => $sale]) }}" class="hover:text-indigo-700">
                            Sale #{{ $sale->sale_number ?? $sale->id }}
                        </a>
                    </p>
                    <p class="text-sm text-gray-600">{{ optional($sale->customer)->name ?? '—' }} · {{ $sale->created_at->format('M d, Y H:i') }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="font-semibold text-gray-900">@money($sale->total_amount)</span>
                    <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.customer-credit.invoices.show', ['sale' => $sale]) }}">View</x-button>
                    @include('customer-credit.partials.delete-button', [
                        'action' => tenant_route('tenant.customer-credit.invoices.destroy', ['sale' => $sale]),
                        'confirm' => 'Remove this credit invoice from active records? Inventory stock will NOT be restored.',
                    ])
                </div>
            </div>
        </x-card>
    @empty
        <x-card class="text-center text-sm text-gray-500">
            No credit sales yet. Complete a POS sale with payment method <strong>Credit</strong> or <strong>Invoice</strong>.
        </x-card>
    @endforelse
</div>

<div class="mt-6">{{ $sales->links() }}</div>
@endsection
