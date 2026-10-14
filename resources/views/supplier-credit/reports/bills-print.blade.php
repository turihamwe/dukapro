@extends('layouts.print')

@section('title', 'Vendor bills')

@push('styles')
<style>
    @media print {
        @page { size: landscape; margin: 1cm; }
    }
</style>
@endpush

@push('print-actions')
    <x-document-share-toolbar
        layout="inline"
        :show-print="false"
        :whats-app-href="$shareWhatsAppUrl"
        :whats-app-message="$shareMessage"
        :email-href="$shareEmailUrl"
    />
@endpush

@section('content')
@include('supplier-credit.reports._print-header', [
    'title' => 'Vendor bills',
    'business' => $business,
    'generatedAt' => $generatedAt,
    'filterLines' => ['Filter: ' . $filterLabel, 'Records: ' . number_format($totals['count'])],
])

<table class="mt-6 w-full border-collapse text-xs">
    <thead>
        <tr class="border-b-2 border-gray-800 text-left uppercase tracking-wide text-gray-600">
            <th class="py-2 pr-3">Bill date</th>
            <th class="py-2 pr-3">Vendor</th>
            <th class="py-2 pr-3">Reference</th>
            <th class="py-2 pr-3 text-right">Total</th>
            <th class="py-2 pr-3 text-right">Paid</th>
            <th class="py-2 pr-3 text-right">Balance</th>
            <th class="py-2">Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($purchases as $purchase)
            <tr class="border-b border-gray-200">
                <td class="py-2 pr-3 whitespace-nowrap">{{ $purchase->purchase_date->format('M j, Y') }}</td>
                <td class="py-2 pr-3">{{ $purchase->supplier->name ?? '—' }}</td>
                <td class="py-2 pr-3">{{ $purchase->reference ?: '—' }}</td>
                <td class="py-2 pr-3 text-right">@money($purchase->total_amount)</td>
                <td class="py-2 pr-3 text-right">@money($purchase->amount_paid)</td>
                <td class="py-2 pr-3 text-right font-medium">@money($purchase->balanceDue())</td>
                <td class="py-2 capitalize">{{ $purchase->status }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="border-t-2 border-gray-800 font-semibold">
            <td class="py-3 pr-3" colspan="3">Totals ({{ number_format($totals['count']) }} bills)</td>
            <td class="py-3 pr-3 text-right">@money($totals['total_amount'])</td>
            <td class="py-3 pr-3 text-right">@money($totals['total_paid'])</td>
            <td class="py-3 pr-3 text-right">@money($totals['total_outstanding'])</td>
            <td></td>
        </tr>
    </tfoot>
</table>
@endsection

@push('scripts')
<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script>
@endpush
