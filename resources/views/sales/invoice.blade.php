@extends('layouts.print')

@section('title', 'Invoice ' . $sale->sale_number)

@section('content')
@if(session('success'))
    <div class="no-print mx-auto mb-4 max-w-md rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        {{ session('success') }}
    </div>
@endif

<div class="mx-auto max-w-md text-sm">
    <div class="border-b border-gray-200 pb-4 text-center">
        <h1 class="text-lg font-bold text-gray-900">{{ $business->name }}</h1>
        @if($business->address)
            <p class="mt-1 text-xs text-gray-500">{{ $business->address }}</p>
        @endif
        @if($business->phone)
            <p class="text-xs text-gray-500">{{ $business->phone }}</p>
        @endif
        <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-amber-700">Tax Invoice</p>
        <p class="font-bold text-gray-900">{{ $sale->sale_number }}</p>
        <p class="text-xs text-gray-500">{{ optional($sale->completed_at)->format('M j, Y g:i A') }}</p>
    </div>

    <div class="my-4 grid grid-cols-2 gap-3 text-xs">
        <div>
            <p class="uppercase text-gray-500">Cashier</p>
            <p class="font-medium text-gray-900">{{ optional($sale->user)->name ?? '—' }}</p>
        </div>
        @if($sale->customer)
            <div>
                <p class="uppercase text-gray-500">Bill to</p>
                <p class="font-medium text-gray-900">{{ $sale->customer->name }}</p>
                @if($sale->customer->phone)
                    <p class="text-gray-600">{{ $sale->customer->phone }}</p>
                @endif
            </div>
        @endif
        @if($sale->invoice_due_at)
            <div class="col-span-2 rounded-lg border border-amber-200 bg-amber-50 p-3">
                <p class="uppercase text-amber-800">Payment due</p>
                <p class="font-semibold text-amber-900">{{ $sale->invoice_due_at->format('M j, Y') }}</p>
            </div>
        @endif
    </div>

    <table class="mb-4 w-full text-xs">
        <thead>
            <tr class="border-b border-gray-200 text-left uppercase text-gray-500">
                <th class="pb-2">Item</th>
                <th class="pb-2 text-center">Qty</th>
                <th class="pb-2 text-right">Amount</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($sale->items as $item)
                <tr>
                    <td class="py-2 pr-2">
                        <p class="font-medium text-gray-900">{{ $item->product_name }}</p>
                        @if($item->notes)
                            <p class="text-orange-700">Note: {{ $item->notes }}</p>
                        @endif
                    </td>
                    <td class="py-2 text-center">{{ format_unit_quantity($item->quantity, $item->measurement_unit ?? 'piece', $business->id) }}</td>
                    <td class="py-2 text-right">@money($item->subtotal)</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="space-y-1 border-t border-gray-200 pt-3 text-xs">
        <div class="flex justify-between">
            <span class="text-gray-600">Subtotal</span>
            <span>@money($sale->subtotal)</span>
        </div>
        @if($sale->discount_amount > 0)
            <div class="flex justify-between">
                <span class="text-gray-600">Discount</span>
                <span>-@money($sale->discount_amount)</span>
            </div>
        @endif
        @if($sale->tax_amount > 0)
            <div class="flex justify-between">
                <span class="text-gray-600">Tax</span>
                <span>@money($sale->tax_amount)</span>
            </div>
        @endif
        <div class="flex justify-between text-base font-bold text-gray-900">
            <span>Amount</span>
            <span>@money($sale->total)</span>
        </div>
        @if($sale->customer)
            <div class="flex justify-between pt-1">
                <span class="text-gray-600">Customer balance</span>
                <span class="font-medium">@money($sale->customer->outstanding_balance)</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Credit limit</span>
                <span>@money($sale->customer->credit_limit)</span>
            </div>
        @endif
    </div>

    @if($sale->notes)
        <p class="mt-3 text-xs text-gray-500">Note: {{ $sale->notes }}</p>
    @endif
</div>

<div class="no-print mx-auto mt-8 max-w-md rounded-xl border border-gray-200 bg-gray-50 p-4">
    <p class="text-sm font-semibold text-gray-900">Send to customer</p>
    <p class="mt-1 text-xs text-gray-500">Share this invoice via WhatsApp or print a copy.</p>
    <div class="mt-3 flex flex-col gap-2 sm:flex-row">
        <input type="tel" id="invoicePhone" value="{{ old('phone', $defaultPhone) }}" placeholder="e.g. 0700123456"
               class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <a id="invoiceWhatsAppBtn" href="{{ $whatsAppUrl }}" target="_blank" rel="noopener noreferrer"
           class="inline-flex items-center justify-center rounded-lg bg-[#25D366] px-4 py-2 text-sm font-semibold text-white hover:bg-[#1ebe5d]">
            WhatsApp
        </a>
    </div>
    <div class="mt-3 grid grid-cols-1 gap-2 {{ \App\Support\SaleDocument::hasCompanionReceipt($sale) ? 'sm:grid-cols-3' : '' }}">
        <button type="button" onclick="window.print()"
                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-50">
            Print invoice
        </button>
        @if(\App\Support\SaleDocument::hasCompanionReceipt($sale))
            <a href="{{ \App\Support\SaleDocument::receiptUrl($sale) }}" target="_blank"
               class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-50">
                Print receipt
            </a>
            <button type="button" id="printBothDocumentsBtn"
                    class="rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-800 hover:bg-indigo-100">
                Print both
            </button>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var phoneInput = document.getElementById('invoicePhone');
    var whatsappBtn = document.getElementById('invoiceWhatsAppBtn');
    var message = @json($invoiceMessage);

    function normalizePhone(value) {
        var digits = (value || '').replace(/\D/g, '');
        if (digits.length === 9) return '256' + digits;
        if (digits.length === 10 && digits.charAt(0) === '0') return '256' + digits.slice(1);
        return digits;
    }

    function updateWhatsAppLink() {
        var digits = normalizePhone(phoneInput.value);
        var base = digits ? 'https://wa.me/' + digits : 'https://wa.me/';
        whatsappBtn.href = base + '?text=' + encodeURIComponent(message);
    }

    phoneInput.addEventListener('input', updateWhatsAppLink);
    updateWhatsAppLink();

    var printBothBtn = document.getElementById('printBothDocumentsBtn');
    if (printBothBtn) {
        printBothBtn.addEventListener('click', function () {
            window.open(@json(\App\Support\SaleDocument::receiptUrl($sale)), '_blank');
            window.print();
        });
    }
})();
</script>
@endpush
