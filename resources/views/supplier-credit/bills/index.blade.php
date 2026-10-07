@extends('layouts.admin')

@section('title', 'Bills')
@section('container_class', 'max-w-5xl')

@section('content')
<x-page-header title="Bills" subtitle="Supplier credit invoices — open, partial, and paid">
    <x-slot name="actions">
        <x-button variant="primary" size="sm" href="{{ tenant_route('tenant.supplier-credit.receives.create') }}">+ Purchase receive</x-button>
    </x-slot>
</x-page-header>

<div class="mb-6 grid gap-4 sm:grid-cols-2">
    <x-card class="!p-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total outstanding</p>
        <p class="mt-2 text-2xl font-bold text-gray-900">@money($totalPayable)</p>
    </x-card>
    <x-card class="!p-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Showing</p>
        <p class="mt-2 text-sm font-medium text-gray-700">{{ $purchases->total() }} bill(s)</p>
    </x-card>
</div>

<div class="mb-4 flex flex-wrap gap-2">
    @php
        $filters = [
            null => 'All',
            'open' => 'Open',
            'partial' => 'Partially paid',
            'paid' => 'Paid',
        ];
    @endphp
    @foreach($filters as $value => $label)
        <a href="{{ tenant_route('tenant.supplier-credit.bills.index', $value ? ['status' => $value] : []) }}"
           class="rounded-full px-3 py-1 text-xs font-medium transition {{ $statusFilter === $value ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

<div class="space-y-3">
    @forelse($purchases as $purchase)
        @php
            $balance = $purchase->balanceDue();
            $line = $purchase->lines->first();
            if ($purchase->is_opening_balance) {
                $productLabel = 'Opening balance (no stock added)';
            } else {
                $productLabel = $line && $line->product ? $line->product->displayName() : 'Stock item';
            }
        @endphp
        <x-card class="!p-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-sm font-semibold text-gray-900">{{ $purchase->supplier->name ?? 'Vendor' }}</p>
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium capitalize
                            @if($purchase->status === 'paid') bg-emerald-100 text-emerald-800
                            @elseif($purchase->status === 'partial') bg-amber-100 text-amber-900
                            @else bg-gray-100 text-gray-700 @endif">{{ $purchase->status }}</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-600">{{ $productLabel }} · {{ $purchase->purchase_date->format('M j, Y') }}</p>
                </div>
                <div class="flex shrink-0 flex-col items-end gap-2 text-right text-sm">
                    <div>
                        <p class="text-xs text-gray-500">Balance</p>
                        <p class="font-semibold {{ $balance > 0 ? 'text-amber-800' : 'text-emerald-700' }}">@money($balance)</p>
                        <p class="mt-1 text-xs text-gray-500">
                            Paid @money($purchase->amount_paid) · Total @money($purchase->total_amount)
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.supplier-credit.bills.show', ['purchase' => $purchase]) }}">View</x-button>
                        @include('supplier-credit.partials.delete-button', [
                            'action' => tenant_route('tenant.supplier-credit.bills.destroy', ['purchase' => $purchase]),
                            'confirm' => 'Are you sure you want to delete this bill? Stock already received will stay in inventory.',
                            'label' => 'Delete',
                        ])
                    </div>
                </div>
            </div>
        </x-card>
    @empty
        <x-card class="text-center text-sm text-gray-500">
            No bills yet.
            <a href="{{ tenant_route('tenant.supplier-credit.receives.create') }}" class="font-medium text-indigo-600">Record a purchase receive on credit</a>.
        </x-card>
    @endforelse
</div>

<div class="mt-6">{{ $purchases->links() }}</div>
@endsection
