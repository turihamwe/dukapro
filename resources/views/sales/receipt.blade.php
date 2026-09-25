@extends('layouts.print')

@section('title', 'Receipt ' . $sale->sale_number)

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
        <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-gray-500">E-Receipt</p>
        <p class="font-bold text-gray-900">{{ $sale->sale_number }}</p>
        <p class="text-xs text-gray-500">{{ optional($sale->completed_at)->format('M j, Y g:i A') }}</p>
    </div>

    <div class="my-4 grid grid-cols-2 gap-3 text-xs">
        <div>
            <p class="uppercase text-gray-500">Cashier</p>
            <p class="font-medium text-gray-900">{{ optional($sale->user)->name ?? '—' }}</p>
        </div>
        @if($sale->waiter)
            <div>
                <p class="uppercase text-gray-500">Server</p>
                <p class="font-medium text-gray-900">{{ $sale->waiter->name }}</p>
            </div>
        @endif
        @if($sale->tableDisplay())
            <div>
                <p class="uppercase text-gray-500">Table</p>
                <p class="font-medium text-gray-900">{{ $sale->tableDisplay() }}</p>
            </div>
        @endif
        @if($sale->customer)
            <div>
                <p class="uppercase text-gray-500">Customer</p>
                <p class="font-medium text-gray-900">{{ $sale->customer->name }}</p>
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
            <span>Total</span>
            <span>@money($sale->total)</span>
        </div>
        <div class="flex justify-between pt-1">
            <span class="text-gray-600">Payment</span>
            <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $sale->payment_method)) }}@if($sale->payment_method === 'mobile_money' && $sale->mobile_money_provider) ({{ strtoupper($sale->mobile_money_provider) }})@endif</span>
        </div>
    </div>

    @if($sale->is_credit_sale && ! $sale->credit_settled_at && ! \App\Support\SaleDocument::hasCompanionReceipt($sale))
        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
            Credit sale — payment pending
        </div>
    @else
        <p class="mt-4 text-center text-sm font-semibold text-emerald-700">PAID — Thank you!</p>
    @endif

    @efrisPlatform
        @if($sale->hasEfrisReceipt())
            <div class="mt-4 border-t border-gray-200 pt-4 text-center">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">URA EFRIS Fiscal Receipt</p>
                @if($sale->efris_fdn)
                    <p class="mt-2 text-sm font-bold text-gray-900">FDN: {{ $sale->efris_fdn }}</p>
                @endif
                @if($sale->efris_antifake_code)
                    <p class="mt-1 text-xs text-gray-600">Verification code: {{ $sale->efris_antifake_code }}</p>
                @endif
                @if($sale->efris_qr_code)
                    <div class="mt-3 flex justify-center">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&amp;data={{ urlencode($sale->efris_qr_code) }}"
                             alt="EFRIS verification QR code" class="h-36 w-36 rounded-lg border border-gray-200 bg-white p-1">
                    </div>
                    <p class="mt-2 break-all text-[10px] text-gray-500">{{ $sale->efris_qr_code }}</p>
                @endif
            </div>
        @elseif($sale->efris_status === 'pending')
            <p class="mt-4 text-center text-xs text-amber-700">EFRIS fiscal receipt is being submitted…</p>
        @elseif($sale->efris_status === 'failed')
            <p class="mt-4 text-center text-xs text-red-600">EFRIS submission pending retry.</p>
        @endif
    @endefrisPlatform

    @if($sale->notes)
        <p class="mt-3 text-xs text-gray-500">Note: {{ $sale->notes }}</p>
    @endif
</div>

<div class="no-print mx-auto mt-8 max-w-md rounded-xl border border-gray-200 bg-gray-50 p-4">
    <p class="text-sm font-semibold text-gray-900">Send to customer</p>
    <p class="mt-1 text-xs text-gray-500">Enter the customer's WhatsApp number, or leave blank to pick a contact in WhatsApp.</p>
    <div class="mt-3 flex flex-col gap-2 sm:flex-row">
        <input type="tel" id="receiptPhone" value="{{ old('phone', $defaultPhone) }}" placeholder="e.g. 0700123456"
               class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <a id="whatsappShareBtn" href="{{ $whatsAppUrl }}" target="_blank" rel="noopener noreferrer"
           class="inline-flex items-center justify-center rounded-lg bg-[#25D366] px-4 py-2 text-sm font-semibold text-white hover:bg-[#1ebe5d]">
            WhatsApp
        </a>
    </div>
    @if(\App\Support\SaleDocument::hasCompanionReceipt($sale))
        <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
            <a href="{{ \App\Support\SaleDocument::invoiceUrl($sale) }}" target="_blank"
               class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-50">
                View invoice
            </a>
            <button type="button" onclick="window.print()"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-50">
                Print receipt
            </button>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    var phoneInput = document.getElementById('receiptPhone');
    var whatsappBtn = document.getElementById('whatsappShareBtn');
    var message = @json($receiptMessage);

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
})();
</script>
@endpush
