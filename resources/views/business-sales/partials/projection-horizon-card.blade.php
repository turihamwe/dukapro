@php
    $growth = $horizon['growth'] ?? [];
    $projections = $horizon['projections'] ?? [];
    $growthColor = function ($value) {
        if ($value === null) return 'text-gray-500';
        if ($value > 0) return 'text-emerald-600';
        if ($value < 0) return 'text-rose-600';
        return 'text-gray-500';
    };
@endphp

<div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:col-span-2 xl:col-span-4">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $horizon['label'] ?? ucfirst($key) }}</p>
            <p class="mt-1 text-xs text-gray-400">{{ $horizon['period_start'] ?? '' }} — {{ $horizon['period_end'] ?? '' }}</p>
        </div>
        <div class="rounded-lg bg-emerald-50 px-3 py-2 text-right">
            <p class="text-[10px] uppercase tracking-wide text-emerald-700">30-day projection</p>
            <p class="text-sm font-bold text-emerald-800">{{ number_format($projections['registrations'] ?? 0, 0) }} signups · {{ number_format($projections['subscriptions'] ?? 0, 0) }} subs</p>
        </div>
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
            <div class="flex items-center justify-between">
                <p class="text-[10px] uppercase tracking-wide text-gray-500">New signups</p>
                <a href="{{ $horizon['view_routes']['registrations'] ?? '#' }}" class="text-[10px] font-semibold text-violet-600 hover:text-violet-800">View</a>
            </div>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($horizon['registrations'] ?? 0) }}</p>
            @if(isset($growth['registrations']) && $growth['registrations'] !== null)
                <p class="text-xs font-medium {{ $growthColor($growth['registrations']) }}">
                    {{ $growth['registrations'] >= 0 ? '+' : '' }}{{ number_format($growth['registrations'], 1) }}% vs prior
                </p>
            @endif
            <p class="mt-1 text-[10px] text-gray-400">Annual pace: {{ number_format($projections['annual_registrations'] ?? 0) }}</p>
        </div>

        <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
            <div class="flex items-center justify-between">
                <p class="text-[10px] uppercase tracking-wide text-gray-500">Catalog activations</p>
                <a href="{{ $horizon['view_routes']['catalog'] ?? '#' }}" class="text-[10px] font-semibold text-violet-600 hover:text-violet-800">View</a>
            </div>
            <p class="mt-1 text-2xl font-bold text-indigo-700">{{ number_format($horizon['catalog'] ?? 0) }}</p>
            @if(isset($growth['catalog']) && $growth['catalog'] !== null)
                <p class="text-xs font-medium {{ $growthColor($growth['catalog']) }}">
                    {{ $growth['catalog'] >= 0 ? '+' : '' }}{{ number_format($growth['catalog'], 1) }}% vs prior
                </p>
            @endif
            <p class="mt-1 text-[10px] text-gray-400">30-day proj: {{ number_format($projections['catalog'] ?? 0, 0) }}</p>
        </div>

        <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
            <div class="flex items-center justify-between">
                <p class="text-[10px] uppercase tracking-wide text-gray-500">New subscriptions</p>
                <a href="{{ $horizon['view_routes']['subscriptions'] ?? '#' }}" class="text-[10px] font-semibold text-violet-600 hover:text-violet-800">View</a>
            </div>
            <p class="mt-1 text-2xl font-bold text-violet-700">{{ number_format($horizon['subscriptions'] ?? 0) }}</p>
            @if(isset($growth['subscriptions']) && $growth['subscriptions'] !== null)
                <p class="text-xs font-medium {{ $growthColor($growth['subscriptions']) }}">
                    {{ $growth['subscriptions'] >= 0 ? '+' : '' }}{{ number_format($growth['subscriptions'], 1) }}% vs prior
                </p>
            @endif
            <p class="mt-1 text-[10px] text-gray-400">Annual pace: {{ number_format($projections['annual_subscriptions'] ?? 0) }}</p>
        </div>

        <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
            <p class="text-[10px] uppercase tracking-wide text-gray-500">Subscription revenue</p>
            <p class="mt-1 text-2xl font-bold text-emerald-700">UGX {{ number_format($horizon['subscription_revenue'] ?? 0, 0) }}</p>
            @if(isset($growth['subscription_revenue']) && $growth['subscription_revenue'] !== null)
                <p class="text-xs font-medium {{ $growthColor($growth['subscription_revenue']) }}">
                    {{ $growth['subscription_revenue'] >= 0 ? '+' : '' }}{{ number_format($growth['subscription_revenue'], 1) }}% vs prior
                </p>
            @endif
            <p class="mt-1 text-[10px] text-gray-400">Annual pace: UGX {{ number_format($projections['annual_revenue'] ?? 0, 0) }}</p>
        </div>
    </div>
</div>
