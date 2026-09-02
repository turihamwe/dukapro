@php
    $business = $reconciliation->business;
    $missingMoney = $reconciliation->missing_money ?? 0;
@endphp

<div class="mb-6 border-b border-gray-200 pb-4">
    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ platform_brand('name') }} EOD Report</p>
    <h1 class="mt-1 text-xl font-bold text-gray-900">{{ $business->name }}</h1>
    <p class="text-sm text-gray-600">{{ $reconciliation->reconciliation_date->format('l, F j, Y') }} · {{ $reconciliation->user->name }}</p>
</div>

<div class="mb-6 grid gap-3 sm:grid-cols-3">
    <div class="rounded-xl border border-gray-200 p-4">
        <p class="text-xs uppercase text-gray-500">Total sales</p>
        <p class="mt-1 text-lg font-bold">@money($report['total_sales'])</p>
    </div>
    <div class="rounded-xl border border-red-100 bg-red-50 p-4">
        <p class="text-xs uppercase text-red-700">Expenses</p>
        <p class="mt-1 text-lg font-bold text-red-700">@money($report['total_expenses'])</p>
    </div>
    <div class="rounded-xl border border-amber-100 bg-amber-50 p-4">
        <p class="text-xs uppercase text-amber-800">Damages</p>
        <p class="mt-1 text-lg font-bold text-amber-900">@money($report['total_damages'])</p>
    </div>
</div>

<div class="mb-6 rounded-xl border border-indigo-200 bg-indigo-50 p-5">
    <h2 class="text-sm font-semibold text-indigo-950">Cash balancing</h2>
    <dl class="mt-4 space-y-2 text-sm">
        <div class="flex justify-between"><dt class="text-indigo-900/70">Expected cash</dt><dd class="font-semibold text-indigo-950">@money($reconciliation->expected_cash)</dd></div>
        <div class="flex justify-between"><dt class="text-indigo-900/70">Actual cash</dt><dd class="font-medium">@money($reconciliation->actual_cash)</dd></div>
        <div class="flex justify-between"><dt class="text-indigo-900/70">Mobile money</dt><dd class="font-medium">@money($reconciliation->actual_mobile_money)</dd></div>
        <div class="flex justify-between"><dt class="text-indigo-900/70">Bank &amp; other</dt><dd class="font-medium">@money($reconciliation->actual_bank_other ?? 0)</dd></div>
        <div class="flex justify-between"><dt class="text-indigo-900/70">Expenses</dt><dd class="font-medium text-red-700">@money($reconciliation->total_expenses ?? 0)</dd></div>
        <div class="flex justify-between"><dt class="text-indigo-900/70">Damages</dt><dd class="font-medium text-amber-800">@money($reconciliation->total_damages ?? 0)</dd></div>
        <div class="flex justify-between border-t border-indigo-200 pt-2">
            <dt class="font-semibold text-indigo-950">Missing money</dt>
            <dd class="font-bold {{ $missingMoney >= 0 ? 'text-emerald-700' : 'text-red-700' }}">@money($missingMoney)</dd>
        </div>
    </dl>
</div>

@if(! empty($report['waiter_balances']) && $report['waiter_balances']->isNotEmpty())
<div class="mb-6 overflow-hidden rounded-xl border border-violet-200 bg-violet-50">
    <div class="border-b border-violet-200 px-5 py-3">
        <h2 class="text-sm font-semibold text-violet-950">Waiter shift balancing</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[520px] text-left text-sm">
            <thead class="bg-violet-100/60 text-xs uppercase tracking-wide text-violet-900/70">
                <tr>
                    <th class="px-5 py-2">Waiter</th>
                    <th class="px-5 py-2">Cash</th>
                    <th class="px-5 py-2">Mobile</th>
                    <th class="px-5 py-2">Merchant</th>
                    <th class="px-5 py-2">Credit</th>
                    <th class="px-5 py-2">Shortage</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-violet-200/60">
                @foreach($report['waiter_balances'] as $balance)
                    <tr>
                        <td class="px-5 py-3 font-medium text-violet-950">{{ $balance->waiter->name ?? 'Staff' }}</td>
                        <td class="px-5 py-3">@money($balance->actual_cash)</td>
                        <td class="px-5 py-3">@money($balance->actual_mobile_airtel + $balance->actual_mobile_mtn)</td>
                        <td class="px-5 py-3">@money($balance->actual_bank_other)</td>
                        <td class="px-5 py-3">@money($balance->actual_credit_collected)</td>
                        <td class="px-5 py-3 font-semibold {{ $balance->shortage >= 0 ? 'text-emerald-700' : 'text-red-700' }}">@money($balance->shortage)</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if($reconciliation->notes)
    <div class="rounded-xl border border-gray-200 p-4 text-sm">
        <p class="text-xs font-semibold uppercase text-gray-500">Notes</p>
        <p class="mt-2 text-gray-700">{{ $reconciliation->notes }}</p>
    </div>
@endif
