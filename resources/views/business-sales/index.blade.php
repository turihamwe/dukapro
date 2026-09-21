@extends('layouts.superadmin')

@section('title', 'Business Sales & Subscriptions')

@section('content')
@include('business-sales.partials.subnav')
<div class="space-y-10" x-data="{ focusHorizon: '{{ $sales['focus_horizon'] ?? 'monthly' }}' }">
    <div>
        <h1 class="text-2xl font-bold tracking-tight">Business Sales &amp; Subscriptions</h1>
        <p class="mt-1 text-sm text-gray-500">SaaS onboarding funnel, subscription growth, and conversion velocity across DukaPro</p>
    </div>

    {{-- Executive summary --}}
    <div class="overflow-hidden rounded-2xl border border-slate-800 bg-gradient-to-br from-slate-900 via-indigo-950 to-violet-950 p-6 text-white shadow-lg sm:p-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-xs font-semibold uppercase tracking-wider text-violet-300">SaaS growth summary</p>
                <p class="mt-3 text-base leading-relaxed text-slate-100 sm:text-lg">{{ $sales['executive_summary'] }}</p>
            </div>
            <div class="grid shrink-0 grid-cols-2 gap-3 sm:grid-cols-1 lg:w-52">
                <div class="rounded-lg border border-white/10 bg-white/5 px-4 py-3">
                    <p class="text-[10px] uppercase tracking-wide text-slate-400">Est. MRR</p>
                    <p class="mt-1 text-xl font-bold text-violet-300">UGX {{ number_format($sales['mrr'], 0) }}</p>
                </div>
                <div class="rounded-lg border border-white/10 bg-white/5 px-4 py-3">
                    <p class="text-[10px] uppercase tracking-wide text-slate-400">Paid subscribers</p>
                    <p class="mt-1 text-xl font-bold">{{ number_format($sales['funnel']['subscribed']['count']) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Onboarding funnel --}}
    <section>
        <div class="mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Onboarding funnel</h2>
            <p class="mt-1 text-sm text-gray-500">Track signups → catalog setup → paid subscription conversion</p>
        </div>

        <div class="mb-4 grid gap-4 lg:grid-cols-3">
            @foreach(['registered', 'catalog', 'subscribed'] as $stageKey)
                @include('business-sales.partials.funnel-stage-card', [
                    'stage' => $sales['funnel'][$stageKey],
                    'conversions' => $sales['funnel']['conversions'],
                    'stageKey' => $stageKey,
                ])
            @endforeach
        </div>

        {{-- Funnel conversion bar --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Stage conversion rates</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <div>
                    <div class="flex items-baseline justify-between text-sm">
                        <span class="text-gray-600">Signup → Catalog</span>
                        <span class="font-bold text-indigo-700">{{ number_format($sales['funnel']['conversions']['registered_to_catalog'], 1) }}%</span>
                    </div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100">
                        <div class="h-full rounded-full bg-indigo-500" style="width: {{ min(100, $sales['funnel']['conversions']['registered_to_catalog']) }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex items-baseline justify-between text-sm">
                        <span class="text-gray-600">Catalog → Subscribed</span>
                        <span class="font-bold text-violet-700">{{ number_format($sales['funnel']['conversions']['catalog_to_subscribed'], 1) }}%</span>
                    </div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100">
                        <div class="h-full rounded-full bg-violet-500" style="width: {{ min(100, $sales['funnel']['conversions']['catalog_to_subscribed']) }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex items-baseline justify-between text-sm">
                        <span class="text-gray-600">Signup → Subscribed</span>
                        <span class="font-bold text-emerald-700">{{ number_format($sales['funnel']['conversions']['registered_to_subscribed'], 1) }}%</span>
                    </div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100">
                        <div class="h-full rounded-full bg-emerald-500" style="width: {{ min(100, $sales['funnel']['conversions']['registered_to_subscribed']) }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Funnel trend chart --}}
    @include('business-sales.partials.funnel-trend-chart', ['fromYear' => $fromYear ?? ($sales['from_year'] ?? 2026)])

    {{-- Projections --}}
    <section>
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Growth projections</h2>
                <p class="mt-1 text-sm text-gray-500">Velocity-based estimates from {{ \Carbon\Carbon::parse($sales['projections_start'] ?? '2026-09-01')->format('j M Y') }} onwards</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach($sales['horizons'] as $key => $horizon)
                    <button type="button"
                            @click="focusHorizon = '{{ $key }}'"
                            :class="focusHorizon === '{{ $key }}' ? 'bg-violet-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                            class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold transition">
                        {{ $horizon['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="space-y-4">
            @foreach($sales['horizons'] as $key => $horizon)
                <div :class="focusHorizon === '{{ $key }}' ? 'ring-2 ring-violet-400 ring-offset-2 rounded-xl' : ''">
                    @include('business-sales.partials.projection-horizon-card', ['key' => $key, 'horizon' => $horizon])
                </div>
            @endforeach
        </div>

        {{-- All horizons comparison table --}}
        <div class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-6 py-4">
                <h3 class="font-semibold text-gray-900">Multi-period velocity comparison</h3>
                <p class="mt-1 text-xs text-gray-500">New signups, catalog activations, subscriptions, and payment revenue by horizon</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Period</th>
                            <th class="px-4 py-3 text-right">Signups</th>
                            <th class="px-4 py-3 text-right">Catalog</th>
                            <th class="px-4 py-3 text-right">Subscriptions</th>
                            <th class="px-4 py-3 text-right">Revenue (UGX)</th>
                            <th class="px-4 py-3 text-right">30-day proj.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($sales['horizons'] as $key => $horizon)
                            <tr class="hover:bg-gray-50" :class="{ 'bg-violet-50': focusHorizon === '{{ $key }}' }">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $horizon['label'] }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">
                                    {{ number_format($horizon['registrations']) }}
                                    <a href="{{ $horizon['view_routes']['registrations'] }}" class="ml-1 text-xs text-violet-600 hover:text-violet-800">View</a>
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">
                                    {{ number_format($horizon['catalog']) }}
                                    <a href="{{ $horizon['view_routes']['catalog'] }}" class="ml-1 text-xs text-violet-600 hover:text-violet-800">View</a>
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">
                                    {{ number_format($horizon['subscriptions']) }}
                                    <a href="{{ $horizon['view_routes']['subscriptions'] }}" class="ml-1 text-xs text-violet-600 hover:text-violet-800">View</a>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-violet-700 tabular-nums">{{ number_format($horizon['subscription_revenue'], 0) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-emerald-700">{{ number_format($horizon['projections']['registrations'], 0) }} signups</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
@endsection
