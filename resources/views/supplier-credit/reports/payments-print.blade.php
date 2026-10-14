@extends('layouts.print')

@section('title', 'Vendor payments')

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
    'title' => 'Vendor payments',
    'business' => $business,
    'generatedAt' => $generatedAt,
    'filterLines' => ['Records: ' . number_format($totals['count'])],
])

<table class="mt-6 w-full border-collapse text-xs">
    <thead>
        <tr class="border-b-2 border-gray-800 text-left uppercase tracking-wide text-gray-600">
            <th class="py-2 pr-3">Paid at</th>
            <th class="py-2 pr-3">Vendor</th>
            <th class="py-2 pr-3">Bill ref.</th>
            <th class="py-2 pr-3 text-right">Amount</th>
            <th class="py-2 pr-3">Method</th>
            <th class="py-2 pr-3">Reference</th>
            <th class="py-2">Recorded by</th>
        </tr>
    </thead>
    <tbody>
        @foreach($payments as $payment)
            <tr class="border-b border-gray-200">
                <td class="py-2 pr-3 whitespace-nowrap">{{ $payment->paid_at->format('M j, Y g:i A') }}</td>
                <td class="py-2 pr-3">{{ $payment->supplier->name ?? '—' }}</td>
                <td class="py-2 pr-3">{{ optional($payment->purchase)->reference ?: ($payment->purchase ? '#' . $payment->purchase->id : '—') }}</td>
                <td class="py-2 pr-3 text-right font-medium">@money($payment->amount)</td>
                <td class="py-2 pr-3">{{ $payment->payment_method ?: '—' }}</td>
                <td class="py-2 pr-3">{{ $payment->reference ?: '—' }}</td>
                <td class="py-2">{{ $payment->user->name ?? '—' }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="border-t-2 border-gray-800 font-semibold">
            <td class="py-3 pr-3" colspan="3">Total paid ({{ number_format($totals['count']) }} payments)</td>
            <td class="py-3 pr-3 text-right">@money($totals['total_paid'])</td>
            <td class="py-3" colspan="3"></td>
        </tr>
    </tfoot>
</table>
@endsection

@push('scripts')
<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script>
@endpush
