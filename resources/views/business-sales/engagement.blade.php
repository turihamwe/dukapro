@extends('layouts.superadmin')

@section('title', 'Engagement Activity')

@section('content')
@include('business-sales.partials.subnav')

<div class="mb-6">
    <h1 class="text-2xl font-bold tracking-tight">Engagement activity</h1>
    <p class="mt-1 text-sm text-gray-500">Trial businesses by last use — prioritize outreach before trials expire</p>
</div>

<div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-5">
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <p class="text-xs uppercase text-gray-500">On trial</p>
        <p class="mt-1 text-2xl font-bold">{{ number_format($summary['total']) }}</p>
    </div>
    <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-4">
        <p class="text-xs uppercase text-emerald-700">Active</p>
        <p class="mt-1 text-2xl font-bold text-emerald-800">{{ number_format($summary['active']) }}</p>
    </div>
    <div class="rounded-xl border border-amber-200 bg-amber-50/50 p-4">
        <p class="text-xs uppercase text-amber-800">At risk</p>
        <p class="mt-1 text-2xl font-bold text-amber-900">{{ number_format($summary['at_risk']) }}</p>
    </div>
    <div class="rounded-xl border border-rose-200 bg-rose-50/50 p-4">
        <p class="text-xs uppercase text-rose-700">Dormant</p>
        <p class="mt-1 text-2xl font-bold text-rose-800">{{ number_format($summary['dormant']) }}</p>
    </div>
    <div class="col-span-2 rounded-xl border border-violet-200 bg-violet-50/60 p-4 lg:col-span-1">
        <p class="text-xs uppercase text-violet-700">Call priority</p>
        <p class="mt-1 text-2xl font-bold text-violet-900">{{ number_format($summary['priority']) }}</p>
        <p class="mt-1 text-[11px] leading-snug text-violet-700">At risk + trial ends within 7 days</p>
    </div>
</div>

<div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
    <div class="flex flex-wrap gap-2">
        @foreach([
            'all' => 'All trials',
            'priority' => 'Call priority',
            'active' => 'Active',
            'at_risk' => 'At risk',
            'dormant' => 'Dormant',
        ] as $key => $label)
            <a href="{{ route('superadmin.business-sales.engagement', array_filter(['filter' => $key !== 'all' ? $key : null, 'search' => $search ?: null])) }}"
               class="rounded-full px-3 py-1.5 text-xs font-semibold transition {{ $filter === $key ? 'bg-violet-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>
    <form method="GET" class="flex w-full max-w-md gap-2">
        @if($filter !== 'all')
            <input type="hidden" name="filter" value="{{ $filter }}">
        @endif
        <input type="search" name="search" value="{{ $search }}" placeholder="Search name, email, phone…"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <button type="submit" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white">Search</button>
    </form>
</div>

@if($filter === 'priority')
    <p class="mb-4 rounded-lg border border-violet-200 bg-violet-50 px-4 py-3 text-sm text-violet-900">
        Showing <strong>at-risk</strong> trials (inactive 4–14 days) with <strong>7 or fewer trial days left</strong> — best candidates for a conversion call.
    </p>
@endif

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">Business</th>
                    <th class="px-4 py-3">Phone</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Trial days left</th>
                    <th class="px-4 py-3">Last active</th>
                    <th class="px-4 py-3">Activity</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($businesses as $business)
                    @php $tier = $business->engagementTier(); @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $business->name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-gray-700">
                            @if($business->phone)
                                <a href="tel:{{ preg_replace('/\s+/', '', $business->phone) }}" class="text-violet-600 hover:text-violet-800">{{ $business->phone }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700">
                            @if($business->email)
                                <a href="mailto:{{ $business->email }}" class="text-violet-600 hover:text-violet-800">{{ $business->email }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 tabular-nums">
                            @php $daysLeft = $business->trialDaysRemaining(); @endphp
                            @if($daysLeft === null)
                                —
                            @elseif($daysLeft === 0)
                                <span class="font-semibold text-rose-600">Expired</span>
                            @else
                                <span class="{{ $daysLeft <= 7 ? 'font-semibold text-amber-700' : 'text-gray-800' }}">{{ $daysLeft }} day{{ $daysLeft === 1 ? '' : 's' }}</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-gray-600">
                            @if($business->last_active_at)
                                {{ $business->last_active_at->format('M j, Y') }}
                                <span class="block text-xs text-gray-400">{{ $business->last_active_at->diffForHumans() }}</span>
                            @else
                                <span class="text-gray-400">Never recorded</span>
                                @if($business->created_at)
                                    <span class="block text-xs text-gray-400">Joined {{ $business->created_at->format('M j, Y') }}</span>
                                @endif
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @include('business-sales.partials.engagement-badge', ['tier' => $tier])
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">No trial businesses match this filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('superadmin.partials.pagination', ['paginator' => $businesses])
</div>
@endsection
