@extends('layouts.superadmin')

@section('title', 'Extend subscriptions')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold tracking-tight">Extend subscriptions</h1>
    <p class="mt-1 text-sm text-gray-500">Search by business owner name, owner email, username, or business name — then add trial or paid days.</p>
</div>

<form method="GET" action="{{ route('superadmin.subscriptions.extend.index') }}" class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end">
    <div class="min-w-0 flex-1">
        <label for="subscription-search" class="mb-1 block text-sm font-medium text-gray-700">Search</label>
        <input type="search" name="q" id="subscription-search" value="{{ $search }}"
               placeholder="Owner name, email, or business name"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500">
    </div>
    <button type="submit" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500">
        Search
    </button>
    @if($search !== '')
        <a href="{{ route('superadmin.subscriptions.extend.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
            Clear
        </a>
    @endif
</form>

@if($search === '')
    <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center text-sm text-gray-600">
        Enter an owner or business name above to find a tenant.
    </div>
@else
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Business</th>
                        <th class="px-4 py-3">Owner</th>
                        <th class="px-4 py-3">Subscription</th>
                        <th class="px-4 py-3">Business account</th>
                        <th class="px-4 py-3">Access</th>
                        <th class="px-4 py-3">Trial ends</th>
                        <th class="px-4 py-3">Paid until</th>
                        <th class="px-4 py-3">Extend</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($businesses as $business)
                        @php
                            $owner = $business->owner;
                            $ownerEmailKey = $owner ? strtolower(trim((string) $owner->email)) : '';
                            $ownerHasMultipleBusinesses = $ownerEmailKey !== '' && in_array($ownerEmailKey, $multiOwnerEmails ?? [], true);
                            $subscriptionExpired = $business->isSubscriptionExpired();
                            $subscriptionBadge = 'bg-gray-100 text-gray-800';
                            if ($business->subscription_status === 'active') {
                                $subscriptionBadge = 'bg-emerald-100 text-emerald-800';
                            } elseif ($business->subscription_status === 'trial') {
                                $subscriptionBadge = 'bg-sky-100 text-sky-800';
                            } elseif ($business->subscription_status === 'expired') {
                                $subscriptionBadge = 'bg-red-100 text-red-800';
                            } elseif ($business->subscription_status === 'inactive') {
                                $subscriptionBadge = 'bg-gray-200 text-gray-800';
                            }
                        @endphp
                        <tr class="align-top hover:bg-gray-50/80 {{ $ownerHasMultipleBusinesses ? 'bg-violet-50/40' : '' }}">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-900">{{ $business->name }}</p>
                                <p class="text-xs text-gray-500">#{{ $business->id }} · {{ $business->slug }}</p>
                            </td>
                            <td class="px-4 py-3">
                                @if($owner)
                                    <p class="font-medium text-gray-900">{{ $owner->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $owner->email }}</p>
                                    @if($ownerHasMultipleBusinesses)
                                        <p class="mt-1 text-xs font-medium text-violet-700">Same owner — multiple businesses in results</p>
                                    @endif
                                    @if(! $owner->is_active)
                                        <p class="mt-0.5 text-xs text-amber-700">Owner login disabled</p>
                                    @endif
                                @else
                                    <span class="text-xs text-amber-700">No owner user</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold capitalize {{ $subscriptionBadge }}">
                                    {{ $business->subscription_status }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($business->is_active)
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">Active</span>
                                @else
                                    <span class="inline-flex rounded-full bg-gray-200 px-2 py-0.5 text-xs font-semibold text-gray-800">Disabled</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($subscriptionExpired)
                                    <span class="inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-800">Expired</span>
                                @else
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">Valid</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ optional($business->trial_ends_at)->format('M j, Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ optional($business->subscription_ends_at)->format('M j, Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('superadmin.subscriptions.extend.store', ['businessId' => $business->id]) }}" class="space-y-2">
                                    @csrf
                                    <input type="hidden" name="q" value="{{ $search }}">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <input type="number" name="days" min="1" max="730" value="30" required
                                               class="w-20 rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
                                        <span class="text-xs text-gray-500">days</span>
                                        <select name="mode" class="rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
                                            <option value="trial" @selected($business->subscription_status === 'trial')>Trial</option>
                                            <option value="active" @selected($business->subscription_status === 'active')>Paid (active)</option>
                                        </select>
                                    </div>
                                    <input type="text" name="note" maxlength="500" placeholder="Optional note for audit log"
                                           class="w-full min-w-[12rem] rounded-lg border border-gray-300 px-2 py-1.5 text-xs">
                                    <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-500">
                                        Extend
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-gray-500">No businesses match “{{ $search }}”.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($businesses->hasPages())
        <div class="mt-4">
            {{ $businesses->links() }}
        </div>
    @endif
@endif
@endsection
