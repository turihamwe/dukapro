@php
    $growth = $horizon['growth'] ?? [];
    $growthColor = function ($value) {
        if ($value === null) return 'text-gray-500';
        if ($value > 0) return 'text-emerald-600';
        if ($value < 0) return 'text-rose-600';
        return 'text-gray-500';
    };
@endphp

<div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $horizon['label'] ?? ucfirst($key) }}</p>
    <div class="mt-3 space-y-3">
        <div>
            <p class="text-[10px] uppercase tracking-wide text-gray-400">Transactions</p>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($horizon['transaction_count'] ?? 0) }}</p>
            @if(isset($growth['transaction_count']) && $growth['transaction_count'] !== null)
                <p class="text-xs font-medium {{ $growthColor($growth['transaction_count']) }}">
                    {{ $growth['transaction_count'] >= 0 ? '+' : '' }}{{ number_format($growth['transaction_count'], 1) }}% vs prior
                </p>
            @endif
        </div>
        <div class="border-t border-gray-100 pt-3">
            <p class="text-[10px] uppercase tracking-wide text-gray-400">Revenue</p>
            <p class="text-lg font-bold text-violet-700">UGX {{ number_format($horizon['revenue'] ?? 0, 0) }}</p>
            @if(isset($growth['revenue']) && $growth['revenue'] !== null)
                <p class="text-xs font-medium {{ $growthColor($growth['revenue']) }}">
                    {{ $growth['revenue'] >= 0 ? '+' : '' }}{{ number_format($growth['revenue'], 1) }}% vs prior
                </p>
            @endif
        </div>
        <div class="border-t border-gray-100 pt-3">
            <p class="text-[10px] uppercase tracking-wide text-gray-400">Gross profit</p>
            <p class="text-lg font-bold text-emerald-700">UGX {{ number_format($horizon['gross_profit'] ?? 0, 0) }}</p>
            <p class="text-xs text-gray-500">{{ number_format($horizon['gross_margin'] ?? 0, 1) }}% margin</p>
        </div>
    </div>
</div>
