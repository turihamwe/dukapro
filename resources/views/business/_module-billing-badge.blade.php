@php
    use App\Support\BillingMode;

    $billing = $billing ?? ($capability['billing'] ?? []);
    $showBilling = BillingMode::isAddons() && ! empty($billing['billable']);
@endphp

@if($showBilling)
    <div class="mt-2 flex flex-wrap items-center gap-2">
        @if(($billing['monthly_price'] ?? 0) > 0)
            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-800">
                +{{ format_money($billing['monthly_price']) }}/mo add-on
            </span>
        @endif
        @if($billing['comped'] ?? false)
            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-800">Comped</span>
        @elseif($billing['entitled'] ?? false)
            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-800">Active</span>
        @elseif(($capability['enabled'] ?? false))
            <span class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-red-800">Pay to unlock</span>
        @endif
        @if(! empty($billing['subscribed_until']))
            <span class="text-[10px] text-gray-500">Add-on until {{ \Carbon\Carbon::parse($billing['subscribed_until'])->format('M j, Y') }}</span>
        @endif
    </div>
@endif
