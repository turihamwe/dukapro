@extends('layouts.superadmin')

@section('title', $stageLabel)

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <a href="{{ route('superadmin.business-sales.index') }}" class="text-sm text-violet-600 hover:text-violet-800">← Business Sales</a>
        <h1 class="mt-2 text-2xl font-bold tracking-tight">{{ $stageLabel }}</h1>
        <p class="mt-1 text-sm text-gray-500">
            @if($periodLabel)
                Filtered to {{ strtolower($periodLabel) }} · contact details for follow-up
            @else
                All businesses in this funnel stage · contact details for follow-up
            @endif
        </p>
    </div>
    <form method="GET" action="{{ route('superadmin.business-sales.businesses') }}" class="flex w-full max-w-md gap-2">
        <input type="hidden" name="stage" value="{{ $stage }}">
        @if($period !== 'all')
            <input type="hidden" name="period" value="{{ $period }}">
        @endif
        <input type="search" name="search" value="{{ $search }}"
               placeholder="Search name, email, phone…"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-violet-500 focus:outline-none">
        <button type="submit" class="shrink-0 rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500">Search</button>
    </form>
</div>

<div class="mb-4 flex flex-wrap gap-2">
    @foreach([
        'all' => 'All time',
        'daily' => 'Today',
        'weekly' => 'This week',
        'monthly' => 'This month',
        'annual' => 'This year',
    ] as $periodKey => $periodName)
        <a href="{{ route('superadmin.business-sales.businesses', array_filter(['stage' => $stage, 'period' => $periodKey !== 'all' ? $periodKey : null, 'search' => $search ?: null])) }}"
           class="rounded-lg border px-3 py-1.5 text-xs font-semibold transition {{ ($period === $periodKey || ($periodKey === 'all' && $period === 'all')) ? 'border-violet-600 bg-violet-600 text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50' }}">
            {{ $periodName }}
        </a>
    @endforeach
</div>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
        <h2 class="font-semibold">{{ number_format($businesses->total()) }} businesses</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">Business</th>
                    <th class="px-4 py-3">Subscription</th>
                    <th class="px-4 py-3">Products</th>
                    <th class="px-4 py-3">Registered</th>
                    <th class="px-4 py-3">Phone</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($businesses as $business)
                    @php
                        if ($business->subscription_status === 'active') {
                            $statusColors = 'bg-emerald-100 text-emerald-800';
                        } elseif ($business->subscription_status === 'trial') {
                            $statusColors = 'bg-blue-100 text-blue-800';
                        } elseif (in_array($business->subscription_status, ['expired', 'inactive'], true)) {
                            $statusColors = 'bg-rose-100 text-rose-800';
                        } else {
                            $statusColors = 'bg-gray-100 text-gray-800';
                        }
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $business->name }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium capitalize {{ $statusColors }}">
                                {{ $business->subscription_status }}
                            </span>
                            @if($business->subscription_ends_at)
                                <p class="mt-0.5 text-[10px] text-gray-400">until {{ $business->subscription_ends_at->format('M j, Y') }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 tabular-nums">{{ number_format($business->products_count) }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $business->created_at->format('M j, Y') }}</td>
                        <td class="px-4 py-3">
                            @if($business->phone)
                                <a href="tel:{{ preg_replace('/\s+/', '', $business->phone) }}" class="font-medium text-violet-700 hover:text-violet-900">{{ $business->phone }}</a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($business->email)
                                <a href="mailto:{{ $business->email }}" class="font-medium text-violet-700 hover:text-violet-900">{{ $business->email }}</a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('superadmin.entities.show', ['businesses', $business->id]) }}" class="text-xs text-gray-600 hover:text-gray-900">Details</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center text-gray-500">No businesses match this filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($businesses->hasPages())
        <div class="border-t border-gray-200 px-6 py-4">
            {{ $businesses->links() }}
        </div>
    @endif
</div>
@endsection
