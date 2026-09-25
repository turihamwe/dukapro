@extends(auth()->user()->usesCashierExperience() ? 'layouts.cashier' : 'layouts.admin')

@section('title', 'Invoices & Receipts')

@section('content')
@include('layouts.partials.cashier-operations-back')

<x-page-header title="Invoices & Receipts" subtitle="Search, audit, and reprint sales documents" />

<x-card :padding="false">
    <form method="GET" action="{{ tenant_route('tenant.sales.documents') }}" class="border-b border-gray-100 p-4 sm:p-6">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <x-input type="search" name="search" label="Search" value="{{ $search }}" placeholder="Sale #, customer, phone…" />
            <div>
                <label for="type" class="mb-1 block text-sm font-medium text-gray-700">Document</label>
                <select name="type" id="type" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="all" @selected($type === 'all')>All documents</option>
                    <option value="invoice" @selected($type === 'invoice')>Open invoices</option>
                    <option value="receipt" @selected($type === 'receipt')>Receipts</option>
                </select>
            </div>
            <div>
                <label for="status" class="mb-1 block text-sm font-medium text-gray-700">Payment status</label>
                <select name="status" id="status" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="all" @selected($status === 'all')>Any status</option>
                    <option value="open" @selected($status === 'open')>Outstanding credit</option>
                    <option value="paid" @selected($status === 'paid')>Paid / settled</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <x-button variant="primary" type="submit" class="w-full sm:w-auto">Filter</x-button>
                @if($search || $type !== 'all' || $status !== 'all')
                    <a href="{{ tenant_route('tenant.sales.documents') }}" class="inline-flex min-h-[40px] items-center rounded-lg border border-gray-300 px-3 text-sm text-gray-700 hover:bg-gray-50">Clear</a>
                @endif
            </div>
        </div>
    </form>

    <x-sortable-table class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <x-sortable-th column="number" class="px-4 py-3 sm:px-6">Sale #</x-sortable-th>
                    <x-sortable-th column="date" class="px-4 py-3 sm:px-6">Date</x-sortable-th>
                    <x-sortable-th column="customer" class="px-4 py-3 sm:px-6">Customer</x-sortable-th>
                    <x-sortable-th column="type" class="px-4 py-3 sm:px-6">Type</x-sortable-th>
                    <x-sortable-th column="total" align="right" class="px-4 py-3 sm:px-6">Total</x-sortable-th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 sm:px-6">Action</th>
                </tr>
            </thead>
            <tbody x-ref="tbody" class="divide-y divide-gray-100 bg-white">
                @forelse($documents as $sale)
                    @php
                        $isInvoice = \App\Support\SaleDocument::isInvoice($sale);
                    @endphp
                    <tr data-sortable-row
                        data-sort-number="{{ strtolower($sale->sale_number) }}"
                        data-sort-date="{{ optional($sale->completed_at)->timestamp ?? 0 }}"
                        data-sort-customer="{{ strtolower($sale->customer->name ?? 'walk-in') }}"
                        data-sort-type="{{ $isInvoice ? 'invoice' : 'receipt' }}"
                        data-sort-total="{{ (float) $sale->total }}">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 sm:px-6">{{ $sale->sale_number }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 sm:px-6">{{ optional($sale->completed_at)->format('M j, Y g:i A') ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 sm:px-6">
                            @if($sale->customer)
                                <p>{{ $sale->customer->name }}</p>
                                @if($sale->customer->phone)
                                    <p class="text-xs text-gray-400">{{ $sale->customer->phone }}</p>
                                @endif
                            @else
                                <span class="text-gray-400">Walk-in</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm sm:px-6">
                            @if($isInvoice)
                                <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">Invoice</span>
                                @if($sale->invoice_due_at)
                                    <p class="mt-1 text-xs text-gray-500">Due {{ $sale->invoice_due_at->format('M j, Y') }}</p>
                                @endif
                            @else
                                <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">Receipt</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-semibold text-gray-900 sm:px-6">@money($sale->total)</td>
                        <td class="px-4 py-3 text-right text-sm sm:px-6">
                            @can('viewReceipt', $sale)
                                <a href="{{ \App\Support\SaleDocument::url($sale) }}" target="_blank"
                                   class="font-medium text-indigo-600 hover:text-indigo-700">
                                    {{ $isInvoice ? 'View invoice' : 'View receipt' }} →
                                </a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr data-sort-empty="1">
                        <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">No documents found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-sortable-table>

    @if($documents->hasPages())
        <div class="border-t border-gray-100 px-4 py-3 sm:px-6">
            {{ $documents->links() }}
        </div>
    @endif
</x-card>
@endsection
